import React, { createContext, useContext, useReducer, useEffect } from 'react';
import { getMainPrice } from '../utils/priceUtils';

const CartContext = createContext();

// Функция для получения числовой цены товара
const getNumericPrice = (price) => {
  if (!price && price !== 0) return 0;
  
  if (typeof price === 'object' && price !== null) {
    const mainPrice = getMainPrice(price);
    return parseFloat(mainPrice) || 0;
  }
  
  return parseFloat(price) || 0;
};

// Session utilities
const SESSION_KEY = 'user_session';

const getSession = () => {
  try {
    const session = localStorage.getItem(SESSION_KEY);
    return session ? JSON.parse(session) : null;
  } catch (error) {
    console.error('Error getting session:', error);
    return null;
  }
};

const saveSession = (session) => {
  try {
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
  } catch (error) {
    console.error('Error saving session:', error);
  }
};

const clearSession = () => {
  try {
    localStorage.removeItem(SESSION_KEY);
  } catch (error) {
    console.error('Error clearing session:', error);
  }
};

const isSessionValid = (session) => {
  if (!session || !session.expiresAt) return false;
  return new Date(session.expiresAt) > new Date();
};

// Action types для reducer
const CART_ACTIONS = {
  ADD_ITEM: 'ADD_ITEM',
  REMOVE_ITEM: 'REMOVE_ITEM',
  UPDATE_QUANTITY: 'UPDATE_QUANTITY',
  CLEAR_CART: 'CLEAR_CART',
  LOAD_CART: 'LOAD_CART',
  SET_SESSION: 'SET_SESSION',
  UPDATE_SESSION: 'UPDATE_SESSION',
  CLEAR_SESSION: 'CLEAR_SESSION'
};

// Начальное состояние корзины
const initialState = {
  items: [],
  total: 0,
  itemCount: 0,
  session: null
};

// Reducer для управления состоянием корзины
const cartReducer = (state, action) => {
  switch (action.type) {
    case CART_ACTIONS.ADD_ITEM: {
      const { product } = action.payload;
      const existingItem = state.items.find(item => item.product_id === product.product_id);

      let updatedItems;
      if (existingItem) {
        // Увеличиваем количество существующего товара
        updatedItems = state.items.map(item =>
          item.product_id === product.product_id
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      } else {
        // Добавляем новый товар
        updatedItems = [...state.items, { ...product, quantity: 1 }];
      }

      const newTotal = updatedItems.reduce((sum, item) => sum + (getNumericPrice(item.price) * item.quantity), 0);
      const newItemCount = updatedItems.reduce((sum, item) => sum + item.quantity, 0);

      return {
        items: updatedItems,
        total: newTotal,
        itemCount: newItemCount
      };
    }

    case CART_ACTIONS.REMOVE_ITEM: {
      const { productId } = action.payload;
      const updatedItems = state.items.filter(item => item.product_id !== productId);
      
      const newTotal = updatedItems.reduce((sum, item) => sum + (getNumericPrice(item.price) * item.quantity), 0);
      const newItemCount = updatedItems.reduce((sum, item) => sum + item.quantity, 0);

      return {
        items: updatedItems,
        total: newTotal,
        itemCount: newItemCount
      };
    }

    case CART_ACTIONS.UPDATE_QUANTITY: {
      const { productId, quantity } = action.payload;
      
      if (quantity <= 0) {
        // Если количество 0 или меньше, удаляем товар
        return cartReducer(state, { 
          type: CART_ACTIONS.REMOVE_ITEM, 
          payload: { productId } 
        });
      }

      const updatedItems = state.items.map(item =>
        item.product_id === productId
          ? { ...item, quantity }
          : item
      );

      const newTotal = updatedItems.reduce((sum, item) => sum + (getNumericPrice(item.price) * item.quantity), 0);
      const newItemCount = updatedItems.reduce((sum, item) => sum + item.quantity, 0);

      return {
        items: updatedItems,
        total: newTotal,
        itemCount: newItemCount
      };
    }

    case CART_ACTIONS.CLEAR_CART:
      return { ...initialState, session: state.session };

    case CART_ACTIONS.LOAD_CART:
      return { ...action.payload, session: state.session };

    case CART_ACTIONS.SET_SESSION:
      return { ...state, session: action.payload.session };

    case CART_ACTIONS.UPDATE_SESSION:
      return { ...state, session: action.payload.session };

    case CART_ACTIONS.CLEAR_SESSION:
      return { ...state, session: null };

    default:
      return state;
  }
};

// Provider компонент
export const CartProvider = ({ children }) => {
  const [state, dispatch] = useReducer(cartReducer, initialState);

  // Загружаем корзину и сессию из localStorage при инициализации
  useEffect(() => {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
      try {
        const cartData = JSON.parse(savedCart);
        dispatch({ type: CART_ACTIONS.LOAD_CART, payload: cartData });
      } catch (error) {
        console.error('Error loading cart from localStorage:', error);
        localStorage.removeItem('cart');
      }
    }

    // Загружаем сессию
    const session = getSession();
    if (session && isSessionValid(session)) {
      dispatch({ type: CART_ACTIONS.SET_SESSION, payload: { session } });
    } else if (session && !isSessionValid(session)) {
      clearSession();
    }
  }, []);

  // Сохраняем корзину в localStorage при изменениях
  useEffect(() => {
    localStorage.setItem('cart', JSON.stringify(state));
  }, [state]);

  // Функции для работы с корзиной
  const addToCart = (product) => {
    dispatch({ type: CART_ACTIONS.ADD_ITEM, payload: { product } });
  };

  const removeFromCart = (productId) => {
    dispatch({ type: CART_ACTIONS.REMOVE_ITEM, payload: { productId } });
  };

  const updateQuantity = (productId, quantity) => {
    dispatch({ type: CART_ACTIONS.UPDATE_QUANTITY, payload: { productId, quantity } });
  };

  const clearCart = () => {
    dispatch({ type: CART_ACTIONS.CLEAR_CART });
  };

  const getItemQuantity = (productId) => {
    const item = state.items.find(item => item.product_id === productId);
    return item ? item.quantity : 0;
  };

  // Session functions
  const setSession = (session) => {
    saveSession(session);
    dispatch({ type: CART_ACTIONS.SET_SESSION, payload: { session } });
  };

  const updateSession = (session) => {
    saveSession(session);
    dispatch({ type: CART_ACTIONS.UPDATE_SESSION, payload: { session } });
  };

  const clearUserSession = () => {
    clearSession();
    dispatch({ type: CART_ACTIONS.CLEAR_SESSION });
  };

  const getCurrentSession = () => {
    return state.session;
  };

  const value = {
    items: state.items,
    total: state.total,
    itemCount: state.itemCount,
    session: state.session,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    getItemQuantity,
    setSession,
    updateSession,
    clearUserSession,
    getCurrentSession
  };

  return (
    <CartContext.Provider value={value}>
      {children}
    </CartContext.Provider>
  );
};

// Hook для использования контекста корзины
export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

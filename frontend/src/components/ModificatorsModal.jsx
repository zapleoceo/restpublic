import React, { useState, useEffect } from 'react';
import { X, Check } from 'lucide-react';

const ModificatorsModal = ({ isOpen, onClose, product, onConfirm }) => {
  const [modificators, setModificators] = useState([]);
  const [selectedModificators, setSelectedModificators] = useState({});
  const [loading, setLoading] = useState(false);
  const [fetchingModificators, setFetchingModificators] = useState(false);

  useEffect(() => {
    if (isOpen && product) {
      fetchModificators();
      // Инициализируем пустые модификаторы
      setSelectedModificators({});
    }
  }, [isOpen, product]);

  const fetchModificators = async () => {
    if (!product?.product_id) return;
    
    setFetchingModificators(true);
    try {
      const response = await fetch(`/api/products/${product.product_id}/modificators`);
      if (response.ok) {
        const data = await response.json();
        setModificators(data.modificators || []);
        
        // Инициализируем пустые модификаторы для каждого группы
        const initialModificators = {};
        data.modificators?.forEach(group => {
          initialModificators[group.modificator_id] = [];
        });
        setSelectedModificators(initialModificators);
      }
    } catch (error) {
      console.error('Ошибка при получении модификаторов:', error);
    } finally {
      setFetchingModificators(false);
    }
  };

  if (!isOpen || !product) return null;

  const handleModificatorChange = (groupId, modificatorId, checked) => {
    setSelectedModificators(prev => {
      const newState = { ...prev };
      if (!newState[groupId]) newState[groupId] = [];

      if (checked) {
        newState[groupId].push(modificatorId);
      } else {
        newState[groupId] = newState[groupId].filter(id => id !== modificatorId);
      }

      return newState;
    });
  };

  const handleConfirm = () => {
    setLoading(true);
    
    // Формируем модификаторы в нужном формате для Poster API
    const modificatorsArray = Object.entries(selectedModificators)
      .filter(([groupId, modIds]) => modIds.length > 0)
      .map(([groupId, modIds]) => ({
        modificator_id: parseInt(groupId),
        modificator_products: modIds.map(id => parseInt(id))
      }));

    onConfirm({
      ...product,
      modificators: modificatorsArray
    });
    setLoading(false);
  };

  const getTotalPrice = () => {
    let total = 0;
    
    // Базовая цена товара
    let itemPrice = 0;
    if (typeof product.price === 'object' && product.price !== null) {
      itemPrice = parseFloat(product.price['1'] || Object.values(product.price)[0] || 0);
    } else {
      itemPrice = parseFloat(product.price) || 0;
    }
    total += itemPrice;

    // Добавляем цену модификаторов
    modificators.forEach(group => {
      group.products.forEach(product => {
        const isSelected = selectedModificators[group.modificator_id]?.includes(product.product_id);
        if (isSelected) {
          let modPrice = 0;
          if (typeof product.price === 'object' && product.price !== null) {
            modPrice = parseFloat(product.price['1'] || Object.values(product.price)[0] || 0);
          } else {
            modPrice = parseFloat(product.price) || 0;
          }
          total += modPrice;
        }
      });
    });
    
    return total;
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-4 border-b">
          <h2 className="text-xl font-bold text-gray-900">Выберите модификаторы</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X size={24} />
          </button>
        </div>

        <div className="p-4 max-h-[calc(90vh-140px)] overflow-y-auto">
          <div className="mb-4">
            <h3 className="text-lg font-semibold mb-2">{product.product_name}</h3>
            <p className="text-gray-600 text-sm">Выберите нужные модификаторы:</p>
          </div>

          {fetchingModificators ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
            </div>
          ) : modificators.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              У этого товара нет модификаторов
            </div>
          ) : (
            <div className="space-y-4">
              {modificators.map((group) => (
                <div key={group.modificator_id} className="border rounded-lg p-3">
                  <h4 className="font-medium text-gray-700 mb-3">
                    {group.modificator_name}
                    {group.required === '1' && <span className="text-red-500 ml-1">*</span>}
                  </h4>
                  
                  <div className="space-y-2">
                    {group.products.map((product) => (
                      <label key={product.product_id} className="flex items-center space-x-3 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={selectedModificators[group.modificator_id]?.includes(product.product_id) || false}
                          onChange={(e) => handleModificatorChange(
                            group.modificator_id, 
                            product.product_id, 
                            e.target.checked
                          )}
                          className="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                        />
                        <span className="flex-1 text-sm">{product.product_name}</span>
                        {product.price && (
                          <span className="text-sm text-gray-600">
                            +{typeof product.price === 'object' ? 
                              (product.price['1'] || Object.values(product.price)[0] || 0) : 
                              product.price} ₫
                          </span>
                        )}
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="p-4 border-t bg-gray-50">
          <div className="flex justify-between items-center mb-4">
            <span className="text-lg font-semibold">Итого:</span>
            <span className="text-xl font-bold text-orange-600">{getTotalPrice()} ₫</span>
          </div>
          
          <div className="flex space-x-3">
            <button
              onClick={onClose}
              className="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 px-4 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button
              onClick={handleConfirm}
              disabled={loading || fetchingModificators}
              className="flex-1 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white py-3 px-4 rounded-lg transition-colors flex items-center justify-center"
            >
              {loading ? (
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              ) : (
                <>
                  <Check size={20} className="mr-2" />
                  Добавить в корзину
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ModificatorsModal;

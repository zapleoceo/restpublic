const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const fs = require('fs');
const path = require('path');

// Путь к файлу с пользователями
const USERS_FILE_PATH = path.join(__dirname, 'users.json');

// JWT секрет (в продакшене должен быть в переменных окружения)
const JWT_SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key-change-in-production';
const JWT_EXPIRES_IN = '24h'; // Токен действителен 24 часа

// Структура пользователей по умолчанию
const DEFAULT_USERS = {
  admin: {
    username: 'admin',
    passwordHash: null, // Будет установлен при инициализации
    role: 'admin',
    createdAt: new Date().toISOString(),
    lastLogin: null
  }
};

// Инициализация пользователей при первом запуске
function initializeUsers() {
  try {
    if (!fs.existsSync(USERS_FILE_PATH)) {
      // Создаем хеш пароля для admin
      const saltRounds = 12;
      const password = '1q2w#E$R';
      const passwordHash = bcrypt.hashSync(password, saltRounds);
      
      const users = {
        admin: {
          ...DEFAULT_USERS.admin,
          passwordHash
        }
      };
      
      fs.writeFileSync(USERS_FILE_PATH, JSON.stringify(users, null, 2));
      console.log('✅ Пользователи инициализированы');
      return users;
    } else {
      const usersData = fs.readFileSync(USERS_FILE_PATH, 'utf8');
      return JSON.parse(usersData);
    }
  } catch (error) {
    console.error('❌ Ошибка инициализации пользователей:', error.message);
    return DEFAULT_USERS;
  }
}

// Загрузка пользователей
function loadUsers() {
  try {
    if (fs.existsSync(USERS_FILE_PATH)) {
      const usersData = fs.readFileSync(USERS_FILE_PATH, 'utf8');
      return JSON.parse(usersData);
    }
    return initializeUsers();
  } catch (error) {
    console.error('❌ Ошибка загрузки пользователей:', error.message);
    return initializeUsers();
  }
}

// Сохранение пользователей
function saveUsers(users) {
  try {
    fs.writeFileSync(USERS_FILE_PATH, JSON.stringify(users, null, 2));
    return true;
  } catch (error) {
    console.error('❌ Ошибка сохранения пользователей:', error.message);
    return false;
  }
}

// Проверка пароля
function verifyPassword(password, passwordHash) {
  return bcrypt.compareSync(password, passwordHash);
}

// Генерация JWT токена
function generateToken(user) {
  const payload = {
    username: user.username,
    role: user.role,
    iat: Math.floor(Date.now() / 1000)
  };
  
  return jwt.sign(payload, JWT_SECRET, { expiresIn: JWT_EXPIRES_IN });
}

// Проверка JWT токена
function verifyToken(token) {
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    return { valid: true, user: decoded };
  } catch (error) {
    return { valid: false, error: error.message };
  }
}

// Авторизация пользователя
function authenticateUser(username, password) {
  const users = loadUsers();
  const user = users[username];
  
  if (!user) {
    return { success: false, error: 'Пользователь не найден' };
  }
  
  if (!verifyPassword(password, user.passwordHash)) {
    return { success: false, error: 'Неверный пароль' };
  }
  
  // Обновляем время последнего входа
  user.lastLogin = new Date().toISOString();
  saveUsers(users);
  
  // Генерируем токен
  const token = generateToken(user);
  
  return {
    success: true,
    token,
    user: {
      username: user.username,
      role: user.role,
      lastLogin: user.lastLogin
    }
  };
}

// Middleware для проверки авторизации
function requireAuth(req, res, next) {
  const token = req.cookies?.adminToken || req.headers.authorization?.replace('Bearer ', '');
  
  if (!token) {
    return res.status(401).json({ error: 'Требуется авторизация' });
  }
  
  const result = verifyToken(token);
  if (!result.valid) {
    return res.status(401).json({ error: 'Недействительный токен' });
  }
  
  req.user = result.user;
  next();
}

// Middleware для проверки роли админа
function requireAdmin(req, res, next) {
  if (!req.user || req.user.role !== 'admin') {
    return res.status(403).json({ error: 'Доступ запрещен' });
  }
  next();
}

// Изменение пароля
function changePassword(username, oldPassword, newPassword) {
  const users = loadUsers();
  const user = users[username];
  
  if (!user) {
    return { success: false, error: 'Пользователь не найден' };
  }
  
  if (!verifyPassword(oldPassword, user.passwordHash)) {
    return { success: false, error: 'Неверный текущий пароль' };
  }
  
  // Хешируем новый пароль
  const saltRounds = 12;
  const newPasswordHash = bcrypt.hashSync(newPassword, saltRounds);
  
  user.passwordHash = newPasswordHash;
  user.updatedAt = new Date().toISOString();
  
  if (saveUsers(users)) {
    return { success: true, message: 'Пароль успешно изменен' };
  } else {
    return { success: false, error: 'Ошибка сохранения пароля' };
  }
}

// Получение информации о пользователе
function getUserInfo(username) {
  const users = loadUsers();
  const user = users[username];
  
  if (!user) {
    return null;
  }
  
  return {
    username: user.username,
    role: user.role,
    createdAt: user.createdAt,
    lastLogin: user.lastLogin,
    updatedAt: user.updatedAt
  };
}

// Инициализация при загрузке модуля
initializeUsers();

module.exports = {
  authenticateUser,
  verifyToken,
  requireAuth,
  requireAdmin,
  changePassword,
  getUserInfo,
  generateToken
};

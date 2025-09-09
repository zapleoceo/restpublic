<?php

class SecurityValidator
{
    /**
     * Валидация MongoDB ObjectId
     */
    public static function validateObjectId($id)
    {
        if (empty($id) || !is_string($id)) {
            return false;
        }
        
        // MongoDB ObjectId должен быть 24 символа hex
        return preg_match('/^[a-f\d]{24}$/i', $id);
    }
    
    /**
     * Валидация имени пользователя
     */
    public static function validateUsername($username)
    {
        if (empty($username) || !is_string($username)) {
            return false;
        }
        
        // Только буквы, цифры, подчеркивания, длина 3-50 символов
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    /**
     * Валидация пароля
     */
    public static function validatePassword($password)
    {
        if (empty($password) || !is_string($password)) {
            return false;
        }
        
        // Минимум 8 символов, максимум 100
        if (strlen($password) < 8 || strlen($password) > 100) {
            return false;
        }
        
        // Должен содержать хотя бы одну букву и одну цифру
        return preg_match('/^(?=.*[A-Za-z])(?=.*\d).+$/', $password);
    }
    
    /**
     * Валидация email
     */
    public static function validateEmail($email)
    {
        if (empty($email) || !is_string($email)) {
            return false;
        }
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валидация категории изображения
     */
    public static function validateImageCategory($category)
    {
        $allowedCategories = [
            'general', 'intro', 'about', 'menu', 'gallery', 
            'products', 'backgrounds', 'icons'
        ];
        
        return in_array($category, $allowedCategories);
    }
    
    /**
     * Валидация описания
     */
    public static function validateDescription($description)
    {
        if (!is_string($description)) {
            return false;
        }
        
        // Максимум 500 символов, разрешены буквы, цифры, пробелы, знаки препинания
        return strlen($description) <= 500 && 
               preg_match('/^[a-zA-Zа-яА-Я0-9\s\.,!?\-_()]+$/u', $description);
    }
    
    /**
     * Валидация поискового запроса
     */
    public static function validateSearchQuery($query)
    {
        if (!is_string($query)) {
            return false;
        }
        
        // Максимум 100 символов, запрещены специальные символы
        return strlen($query) <= 100 && 
               !preg_match('/[<>"\']/', $query);
    }
    
    /**
     * Валидация номера страницы
     */
    public static function validatePageNumber($page)
    {
        $page = (int)$page;
        return $page > 0 && $page <= 1000;
    }
    
    /**
     * Валидация статуса транзакции
     */
    public static function validateTransactionStatus($status)
    {
        $allowedStatuses = ['success', 'failed', 'pending', 'cancelled'];
        return in_array($status, $allowedStatuses);
    }
    
    /**
     * Валидация суммы
     */
    public static function validateAmount($amount)
    {
        $amount = (float)$amount;
        return $amount >= 0 && $amount <= 999999.99;
    }
    
    /**
     * Валидация даты
     */
    public static function validateDate($date)
    {
        if (empty($date)) {
            return false;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Валидация CSRF токена
     */
    public static function validateCSRFToken($token)
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }
        
        // CSRF токен должен быть 64 символа hex
        return preg_match('/^[a-f\d]{64}$/i', $token);
    }
    
    /**
     * Санитизация строки для вывода в HTML
     */
    public static function sanitizeForHTML($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Санитизация для MongoDB запроса
     */
    public static function sanitizeForMongoDB($string)
    {
        // Удаляем потенциально опасные символы
        return preg_replace('/[<>"\']/', '', $string);
    }
    
    /**
     * Проверка на XSS
     */
    public static function detectXSS($input)
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
}
?>

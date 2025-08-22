const SePayService = require('./sepay-service');
const axios = require('axios');

class SePayMonitor {
    constructor() {
        this.sepayService = new SePayService();
        this.telegramToken = process.env.TELEGRAM_BOT_TOKEN;
        this.chatIds = ['7795513546', '169510539']; // Целевые чаты для уведомлений: Rest_publica_bar, zapleosoft
        this.isRunning = false;
        this.checkInterval = 10000; // 10 секунд
    }

    async sendTelegramMessage(message) {
        for (const chatId of this.chatIds) {
            try {
                const response = await axios.post(`https://api.telegram.org/bot${this.telegramToken}/sendMessage`, {
                    chat_id: chatId,
                    text: message,
                    parse_mode: 'Markdown'
                });

                if (response.data.ok) {
                    console.log(`✅ Уведомление отправлено в Telegram чат ${chatId}: ${message.substring(0, 50)}...`);
                } else {
                    console.error(`❌ Ошибка отправки в Telegram чат ${chatId}:`, response.data);
                }
            } catch (error) {
                console.error(`❌ Ошибка отправки в Telegram чат ${chatId}:`, error.message);
            }
        }
    }

    async checkNewTransactions() {
        try {
            console.log('🔍 Проверка новых транзакций SePay...');
            
            const newTransactions = await this.sepayService.getNewTransactions();
            
            if (newTransactions.length > 0) {
                console.log(`💰 Найдено ${newTransactions.length} новых транзакций`);
                
                for (const transaction of newTransactions) {
                    const message = this.sepayService.formatTransactionMessage(transaction);
                    await this.sendTelegramMessage(message);
                    
                    // Небольшая задержка между отправками
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            } else {
                console.log('📭 Новых транзакций не найдено');
            }
        } catch (error) {
            console.error('❌ Ошибка проверки транзакций:', error.message);
        }
    }

    start() {
        if (this.isRunning) {
            console.log('⚠️ Мониторинг уже запущен');
            return;
        }

        console.log('🚀 Запуск мониторинга транзакций SePay...');
        console.log(`📱 Уведомления будут отправляться в чаты: ${this.chatIds.join(', ')}`);
        console.log(`⏰ Интервал проверки: ${this.checkInterval / 1000} секунд`);
        
        this.isRunning = true;

        // Первая проверка сразу при запуске
        this.checkNewTransactions();

        // Устанавливаем периодическую проверку
        this.interval = setInterval(() => {
            this.checkNewTransactions();
        }, this.checkInterval);
    }

    stop() {
        if (!this.isRunning) {
            console.log('⚠️ Мониторинг не запущен');
            return;
        }

        console.log('🛑 Остановка мониторинга транзакций SePay...');
        this.isRunning = false;
        
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    // Метод для тестирования
    async testConnection() {
        console.log('🧪 Тестирование подключения к SePay API...');
        
        try {
            const transactions = await this.sepayService.getTransactions();
            console.log(`✅ Подключение успешно. Найдено транзакций: ${transactions.length}`);
            
            if (transactions.length > 0) {
                console.log('📋 Последняя транзакция:');
                console.log(JSON.stringify(transactions[0], null, 2));
            }
            
            return true;
        } catch (error) {
            console.error('❌ Ошибка подключения к SePay API:', error.message);
            return false;
        }
    }
}

// Экспорт для использования в других модулях
module.exports = SePayMonitor;

// Если файл запущен напрямую
if (require.main === module) {
    const monitor = new SePayMonitor();
    
    // Обработка сигналов для graceful shutdown
    process.on('SIGINT', () => {
        console.log('\n🛑 Получен сигнал SIGINT, останавливаем мониторинг...');
        monitor.stop();
        process.exit(0);
    });

    process.on('SIGTERM', () => {
        console.log('\n🛑 Получен сигнал SIGTERM, останавливаем мониторинг...');
        monitor.stop();
        process.exit(0);
    });

    // Запуск мониторинга
    monitor.start();
}

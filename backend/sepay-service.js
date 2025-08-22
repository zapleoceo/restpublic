const axios = require('axios');

class SePayService {
    constructor() {
        this.apiToken = process.env.SEPAY_API_TOKEN;
        this.baseUrl = 'https://my.sepay.vn/userapi';
        this.lastTransactionId = null;
    }

    async getTransactions() {
        try {
            const response = await axios.get(`${this.baseUrl}/transactions/list`, {
                headers: {
                    'Authorization': `Bearer ${this.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.data && response.data.status === 200) {
                return response.data.transactions || [];
            }
            
            return [];
        } catch (error) {
            console.error('SePay API Error:', error.message);
            return [];
        }
    }

    async getNewTransactions() {
        const transactions = await this.getTransactions();
        
        if (!transactions.length) {
            return [];
        }

        // Если это первый запуск, сохраняем ID последней транзакции
        if (!this.lastTransactionId) {
            this.lastTransactionId = transactions[0].id;
            return [];
        }

        // Находим новые транзакции (входящие платежи)
        const newTransactions = [];
        for (const transaction of transactions) {
            if (transaction.id === this.lastTransactionId) {
                break; // Достигли последней известной транзакции
            }
            
            // Проверяем, что это входящий платеж
            if (parseFloat(transaction.amount_in) > 0) {
                newTransactions.push(transaction);
            }
        }

        // Обновляем ID последней транзакции
        if (transactions.length > 0) {
            this.lastTransactionId = transactions[0].id;
        }

        return newTransactions;
    }

    formatTransactionMessage(transaction) {
        return `💵 **${transaction.amount_in} VND**

💰 Поступление средств BIDV
📅 Время: ${transaction.transaction_date}
💳 Счет: ${transaction.account_number}
📝 Описание: ${transaction.transaction_content}
💰 Баланс: ${transaction.accumulated} VND
🔢 Номер ссылки: ${transaction.reference_number || 'N/A'}`;
    }
}

module.exports = SePayService;

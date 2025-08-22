import React from 'react';
import { Link } from 'react-router-dom';
import { Home } from 'lucide-react';

const TableErrorPage = ({ tableId }) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md text-center">
        <div className="bg-white rounded-xl shadow-lg p-8">
          <div className="text-6xl mb-4">⚠️</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            Столик не найден
          </h1>
          <p className="text-gray-600 mb-6">
            Столик №{tableId} не существует или был удален.
          </p>
          <p className="text-sm text-gray-500 mb-6">
            Пожалуйста, обратитесь к персоналу ресторана или воспользуйтесь главной страницей сайта.
          </p>
          <Link 
            to="/"
            className="inline-flex items-center px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors"
          >
            <Home className="mr-2 w-4 h-4" />
            Перейти на главную
          </Link>
        </div>
      </div>
    </div>
  );
};

export default TableErrorPage;

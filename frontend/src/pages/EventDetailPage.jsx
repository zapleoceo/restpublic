import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from '../hooks/useTranslation';
import { eventsService } from '../services/eventsService';
import { formatEventDate } from '../utils/formatters';

export const EventDetailPage = () => {
  const { eventId } = useParams();
  const { t } = useTranslation();
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchEvent();
  }, [eventId]);

  const fetchEvent = async () => {
    try {
      setLoading(true);
      const data = await eventsService.getEvent(eventId);
      setEvent(data);
    } catch (err) {
      console.error('Ошибка загрузки события:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-neutral-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error || !event) {
    return (
      <div className="min-h-screen bg-neutral-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">❌</div>
          <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
            Событие не найдено
          </h3>
          <p className="text-neutral-600 mb-4">
            {error || 'Запрашиваемое событие не существует'}
          </p>
          <Link
            to="/events"
            className="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Вернуться к событиям
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="event-detail-page min-h-screen bg-neutral-50">
      <div className="container mx-auto px-4 py-8">
        {/* Хлебные крошки */}
        <nav className="mb-8">
          <ol className="flex items-center space-x-2 text-sm text-neutral-600">
            <li>
              <Link to="/" className="hover:text-primary-600 transition-colors">
                Главная
              </Link>
            </li>
            <li>
              <span className="mx-2">/</span>
            </li>
            <li>
              <Link to="/events" className="hover:text-primary-600 transition-colors">
                События
              </Link>
            </li>
            <li>
              <span className="mx-2">/</span>
            </li>
            <li className="text-neutral-900 font-medium">
              {event.title}
            </li>
          </ol>
        </nav>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Основной контент */}
          <div className="lg:col-span-2">
            {/* Постер события */}
            <div className="mb-8">
              <img
                src={event.poster}
                alt={event.title}
                className="w-full h-96 object-cover rounded-lg shadow-lg"
              />
            </div>

            {/* Заголовок и мета-информация */}
            <div className="mb-8">
              <div className="flex items-center space-x-4 mb-4">
                <span className="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                  {event.category}
                </span>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                  event.status === 'upcoming' ? 'bg-green-100 text-green-800' :
                  event.status === 'ongoing' ? 'bg-blue-100 text-blue-800' :
                  'bg-gray-100 text-gray-800'
                }`}>
                  {event.status === 'upcoming' ? 'Предстоящее' :
                   event.status === 'ongoing' ? 'Текущее' : 'Завершенное'}
                </span>
              </div>

              <h1 className="text-4xl md:text-5xl font-serif font-bold text-primary-900 mb-4">
                {event.title}
              </h1>

              <div className="flex items-center space-x-6 text-neutral-600 mb-6">
                <div className="flex items-center space-x-2">
                  <span className="text-lg">📅</span>
                  <span>{formatEventDate(event.date)}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="text-lg">📍</span>
                  <span>{event.location}</span>
                </div>
                {event.price && (
                  <div className="flex items-center space-x-2">
                    <span className="text-lg">💰</span>
                    <span>{event.price}</span>
                  </div>
                )}
              </div>

              {event.shortDescription && (
                <p className="text-lg text-neutral-700 leading-relaxed mb-6">
                  {event.shortDescription}
                </p>
              )}
            </div>

            {/* Основной контент */}
            {event.content && (
              <div className="prose prose-lg max-w-none">
                <div dangerouslySetInnerHTML={{ __html: event.content }} />
              </div>
            )}
          </div>

          {/* Боковая панель */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 sticky top-8">
              <h3 className="text-xl font-serif font-bold text-primary-900 mb-4">
                Информация о событии
              </h3>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-neutral-600 mb-1">
                    Дата и время
                  </label>
                  <p className="text-neutral-900 font-medium">
                    {new Date(event.date).toLocaleString('ru-RU', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-neutral-600 mb-1">
                    Место проведения
                  </label>
                  <p className="text-neutral-900 font-medium">
                    {event.location}
                  </p>
                </div>

                {event.price && (
                  <div>
                    <label className="block text-sm font-medium text-neutral-600 mb-1">
                      Стоимость
                    </label>
                    <p className="text-neutral-900 font-medium">
                      {event.price}
                    </p>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-neutral-600 mb-1">
                    Категория
                  </label>
                  <p className="text-neutral-900 font-medium capitalize">
                    {event.category}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-neutral-600 mb-1">
                    Статус
                  </label>
                  <p className={`font-medium ${
                    event.status === 'upcoming' ? 'text-green-600' :
                    event.status === 'ongoing' ? 'text-blue-600' :
                    'text-gray-600'
                  }`}>
                    {event.status === 'upcoming' ? 'Предстоящее' :
                     event.status === 'ongoing' ? 'Текущее' : 'Завершенное'}
                  </p>
                </div>
              </div>

              <div className="mt-6 pt-6 border-t border-neutral-200">
                <Link
                  to="/events"
                  className="w-full bg-primary-500 hover:bg-primary-600 text-white py-3 px-4 rounded-lg transition-colors text-center block"
                >
                  Вернуться к событиям
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

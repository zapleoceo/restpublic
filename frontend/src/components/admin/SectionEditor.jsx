import React, { useState } from 'react';
import { BaseButton } from '../ui/BaseButton';
import { WYSIWYGEditor } from './WYSIWYGEditor';
import { ImageUploader } from './ImageUploader';

const getSectionTitle = (section) => {
  const titles = {
    intro: 'Главная секция',
    about: 'О нас',
    menu: 'Меню',
    services: 'Услуги',
    events: 'События',
    testimonials: 'Отзывы'
  };
  return titles[section] || section;
};

const getSectionFields = (section) => {
  const fields = {
    intro: ['title', 'subtitle', 'background_image'],
    about: ['title', 'content', 'images'],
    menu: ['title'],
    services: ['title', 'items'],
    events: ['title', 'description'],
    testimonials: ['title', 'items']
  };
  return fields[section] || [];
};

export const SectionEditor = ({ section, data = {}, onSave }) => {
  const [content, setContent] = useState(data);
  const [isEditing, setIsEditing] = useState(false);
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    setSaving(true);
    try {
      await onSave(section, content);
      setIsEditing(false);
    } catch (error) {
      console.error('Ошибка сохранения:', error);
      alert('Ошибка сохранения: ' + error.message);
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    setContent(data);
    setIsEditing(false);
  };

  const updateField = (field, value) => {
    setContent(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const renderField = (field) => {
    switch (field) {
      case 'title':
        return (
          <div className="mb-4">
            <label className="block text-sm font-medium text-neutral-700 mb-2">
              Заголовок
            </label>
            <input
              type="text"
              value={content.title || ''}
              onChange={(e) => updateField('title', e.target.value)}
              className="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="Введите заголовок"
            />
          </div>
        );

      case 'subtitle':
        return (
          <div className="mb-4">
            <label className="block text-sm font-medium text-neutral-700 mb-2">
              Подзаголовок
            </label>
            <textarea
              value={content.subtitle || ''}
              onChange={(e) => updateField('subtitle', e.target.value)}
              className="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
              rows="3"
              placeholder="Введите подзаголовок"
            />
          </div>
        );

      case 'content':
        return (
          <div className="mb-4">
            <label className="block text-sm font-medium text-neutral-700 mb-2">
              Контент
            </label>
            <WYSIWYGEditor
              value={content.content || ''}
              onChange={(value) => updateField('content', value)}
              placeholder="Введите контент секции..."
            />
          </div>
        );

      case 'background_image':
        return (
          <div className="mb-4">
            <label className="block text-sm font-medium text-neutral-700 mb-2">
              Фоновое изображение
            </label>
            <ImageUploader
              images={content.background_image ? [{ id: 1, url: content.background_image, name: 'background' }] : []}
              onChange={(images) => updateField('background_image', images[0]?.url || '')}
              multiple={false}
            />
          </div>
        );

      case 'images':
        return (
          <div className="mb-4">
            <ImageUploader
              images={content.images || []}
              onChange={(images) => updateField('images', images)}
              multiple={true}
            />
          </div>
        );

      case 'description':
        return (
          <div className="mb-4">
            <label className="block text-sm font-medium text-neutral-700 mb-2">
              Описание
            </label>
            <textarea
              value={content.description || ''}
              onChange={(e) => updateField('description', e.target.value)}
              className="w-full px-3 py-2 border border-neutral-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
              rows="3"
              placeholder="Введите описание"
            />
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="section-editor bg-white rounded-lg shadow-md p-6">
      <div className="section-header flex items-center justify-between mb-6">
        <h3 className="text-xl font-serif font-bold text-primary-900">
          {getSectionTitle(section)}
        </h3>
        <div className="section-controls flex items-center space-x-4">
          <label className="flex items-center space-x-2">
            <input
              type="checkbox"
              checked={content.active !== false}
              onChange={(e) => updateField('active', e.target.checked)}
              className="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-neutral-700">Активна</span>
          </label>
          <BaseButton
            variant={isEditing ? 'secondary' : 'primary'}
            size="sm"
            onClick={() => setIsEditing(!isEditing)}
          >
            {isEditing ? 'Отменить' : 'Редактировать'}
          </BaseButton>
        </div>
      </div>

      {isEditing ? (
        <div className="editor-content">
          <div className="space-y-6">
            {getSectionFields(section).map(field => (
              <div key={field}>
                {renderField(field)}
              </div>
            ))}
          </div>

          <div className="editor-actions flex justify-end space-x-4 mt-6 pt-6 border-t border-neutral-200">
            <BaseButton
              variant="secondary"
              onClick={handleCancel}
              disabled={saving}
            >
              Отмена
            </BaseButton>
            <BaseButton
              variant="primary"
              onClick={handleSave}
              disabled={saving}
            >
              {saving ? 'Сохранение...' : 'Сохранить'}
            </BaseButton>
          </div>
        </div>
      ) : (
        <div className="section-preview">
          {content.content && (
            <div 
              className="prose prose-lg max-w-none"
              dangerouslySetInnerHTML={{ __html: content.content }} 
            />
          )}
          {!content.content && (
            <div className="text-center py-8 text-neutral-500">
              <div className="text-4xl mb-2">📝</div>
              <p>Контент не заполнен</p>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

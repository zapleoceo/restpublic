import React, { useState } from 'react';
import { BaseButton } from '../ui/BaseButton';

export const ImageUploader = ({ images = [], onChange, multiple = true }) => {
  const [uploading, setUploading] = useState(false);

  const handleFileSelect = async (event) => {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;

    setUploading(true);
    
    try {
      const uploadedImages = [];
      
      for (const file of files) {
        // Проверяем тип файла
        if (!file.type.startsWith('image/')) {
          alert(`Файл ${file.name} не является изображением`);
          continue;
        }

        // Проверяем размер файла (максимум 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert(`Файл ${file.name} слишком большой. Максимальный размер: 5MB`);
          continue;
        }

        // Создаем FormData для загрузки
        const formData = new FormData();
        formData.append('image', file);

        try {
          // Здесь должен быть API для загрузки изображений
          // Пока используем base64 для демонстрации
          const reader = new FileReader();
          reader.onload = (e) => {
            uploadedImages.push({
              id: Date.now() + Math.random(),
              url: e.target.result,
              name: file.name,
              size: file.size
            });
            
            if (uploadedImages.length === files.length) {
              const newImages = multiple ? [...images, ...uploadedImages] : uploadedImages;
              onChange(newImages);
              setUploading(false);
            }
          };
          reader.readAsDataURL(file);
        } catch (error) {
          console.error('Ошибка загрузки изображения:', error);
          alert(`Ошибка загрузки ${file.name}`);
        }
      }
    } catch (error) {
      console.error('Ошибка обработки файлов:', error);
      setUploading(false);
    }
  };

  const removeImage = (imageId) => {
    const newImages = images.filter(img => img.id !== imageId);
    onChange(newImages);
  };

  return (
    <div className="image-uploader">
      <div className="mb-4">
        <label className="block text-sm font-medium text-neutral-700 mb-2">
          Изображения
        </label>
        
        <div className="flex items-center space-x-4">
          <BaseButton
            variant="outline"
            size="sm"
            onClick={() => document.getElementById('image-upload').click()}
            disabled={uploading}
          >
            {uploading ? 'Загрузка...' : 'Выбрать изображения'}
          </BaseButton>
          
          <input
            id="image-upload"
            type="file"
            accept="image/*"
            multiple={multiple}
            onChange={handleFileSelect}
            className="hidden"
          />
          
          <span className="text-sm text-neutral-500">
            {multiple ? 'Можно выбрать несколько файлов' : 'Выберите одно изображение'}
          </span>
        </div>
      </div>

      {images.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {images.map((image) => (
            <div key={image.id} className="relative group">
              <img
                src={image.url}
                alt={image.name}
                className="w-full h-32 object-cover rounded-lg border border-neutral-200"
              />
              <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center">
                <button
                  onClick={() => removeImage(image.id)}
                  className="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center transition-opacity duration-200"
                  title="Удалить"
                >
                  ×
                </button>
              </div>
              <div className="mt-1 text-xs text-neutral-500 truncate">
                {image.name}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

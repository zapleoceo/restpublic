import React from 'react';
import { Editor } from '@tinymce/tinymce-react';

export const WYSIWYGEditor = ({ value, onChange, placeholder = "Введите текст..." }) => {
  const handleEditorChange = (content) => {
    onChange(content);
  };

  return (
    <div className="wysiwyg-editor">
      <Editor
        apiKey="your-tinymce-api-key" // Можно получить бесплатно на https://www.tiny.cloud/
        value={value}
        onEditorChange={handleEditorChange}
        init={{
          height: 400,
          menubar: false,
          plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
          ],
          toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
          content_style: `
            body { 
              font-family: 'Roboto Flex', sans-serif; 
              font-size: 14px; 
              line-height: 1.6; 
              color: #333; 
            }
            h1, h2, h3, h4, h5, h6 { 
              font-family: 'Playfair Display', serif; 
              color: #253c35; 
            }
            h1 { font-size: 2em; margin: 0.67em 0; }
            h2 { font-size: 1.5em; margin: 0.75em 0; }
            h3 { font-size: 1.17em; margin: 0.83em 0; }
            h4 { font-size: 1em; margin: 1.12em 0; }
            h5 { font-size: 0.83em; margin: 1.5em 0; }
            h6 { font-size: 0.75em; margin: 1.67em 0; }
            p { margin: 1em 0; }
            ul, ol { margin: 1em 0; padding-left: 2em; }
            li { margin: 0.5em 0; }
            a { color: #468672; text-decoration: underline; }
            a:hover { color: #366b5b; }
            blockquote { 
              border-left: 4px solid #468672; 
              margin: 1em 0; 
              padding-left: 1em; 
              font-style: italic; 
              color: #666; 
            }
            img { max-width: 100%; height: auto; }
            table { 
              border-collapse: collapse; 
              width: 100%; 
              margin: 1em 0; 
            }
            table th, table td { 
              border: 1px solid #ddd; 
              padding: 8px; 
              text-align: left; 
            }
            table th { 
              background-color: #f4f9f7; 
              font-weight: bold; 
            }
          `,
          placeholder: placeholder,
          language: 'ru',
          branding: false,
          elementpath: false,
          resize: false,
          statusbar: false,
          paste_data_images: true,
          images_upload_handler: (blobInfo, progress) => {
            return new Promise((resolve, reject) => {
              // Здесь можно добавить загрузку изображений на сервер
              // Пока возвращаем base64
              const reader = new FileReader();
              reader.onload = () => {
                resolve(reader.result);
              };
              reader.onerror = () => {
                reject('Ошибка загрузки изображения');
              };
              reader.readAsDataURL(blobInfo.blob());
            });
          }
        }}
      />
    </div>
  );
};

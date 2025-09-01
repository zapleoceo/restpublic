import { useState, useEffect } from 'react';
import sectionsService from '../services/sectionsService';

export const useSiteContent = () => {
  const [content, setContent] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchContent = async () => {
      try {
        setLoading(true);
        const data = await sectionsService.getAllSections();
        setContent(data);
        setError(null);
      } catch (err) {
        setError(err.message);
        console.error('Error fetching site content:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchContent();
  }, []);

  return { content, loading, error };
};

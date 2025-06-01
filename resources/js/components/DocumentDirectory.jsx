import { useEffect, useState } from 'react';

export default function DocumentDirectory({ onClose }) {
  const [docs, setDocs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetch('/api/documents', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        setDocs(data);
        setLoading(false);
      })
      .catch(err => {
        setError('Failed to fetch documents.');
        setLoading(false);
      });
  }, []);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 backdrop-blur-sm">
      <div className="bg-white rounded-xl shadow-2xl p-6 w-full max-w-2xl relative">
        <button
          className="absolute top-4 right-4 text-2xl text-gray-700 hover:text-black"
          onClick={onClose}
          aria-label="Close document directory"
        >
          &times;
        </button>
        <h2 className="text-2xl font-bold mb-4">Document Directory</h2>
        {loading ? (
          <div>Loading documents...</div>
        ) : error ? (
          <div className="text-red-600">{error}</div>
        ) : docs.length === 0 ? (
          <div>No documents found for your account.</div>
        ) : (
          <ul className="divide-y">
            {docs.map(doc => (
              <li key={doc.id} className="py-2 flex justify-between items-center">
                <span className="font-medium">{doc.title || doc.original_filename || `Document #${doc.id}`}</span>
                <a
                  href={doc.download_url || doc.file || `/api/documents/${doc.id}/download/`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-green-700 hover:underline font-semibold"
                >
                  View
                </a>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
} 
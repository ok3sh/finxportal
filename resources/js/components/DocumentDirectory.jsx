import { useEffect, useState } from 'react';

export default function DocumentDirectory({ onClose }) {
  const [docs, setDocs] = useState([]);
  const [filteredDocs, setFilteredDocs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    fetch('/api/documents', { credentials: 'include' })
      .then(res => res.json())
      .then(data => {
        setDocs(data);
        setFilteredDocs(data);
        setLoading(false);
      })
      .catch(err => {
        setError('Failed to fetch documents.');
        setLoading(false);
      });
  }, []);

  // Filter documents based on search term
  useEffect(() => {
    if (!searchTerm.trim()) {
      setFilteredDocs(docs);
    } else {
      const filtered = docs.filter(doc => {
        const title = doc.title || doc.original_filename || `Document #${doc.id}`;
        const content = doc.content || '';
        const tags = (doc.tags || []).join(' ');
        
        return title.toLowerCase().includes(searchTerm.toLowerCase()) ||
               content.toLowerCase().includes(searchTerm.toLowerCase()) ||
               tags.toLowerCase().includes(searchTerm.toLowerCase());
      });
      setFilteredDocs(filtered);
    }
  }, [searchTerm, docs]);

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };

  const clearSearch = () => {
    setSearchTerm('');
  };

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
        <input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={handleSearch}
          className="w-full mb-4 px-3 py-2 border rounded"
        />
        {loading ? (
          <div className="text-center py-8">
            <div className="text-gray-500">Loading documents...</div>
          </div>
        ) : error ? (
          <div className="text-center py-8">
          <div className="text-red-600">{error}</div>
          </div>
        ) : docs.length === 0 ? (
          <div className="text-center py-8">
            <div className="text-gray-500">No documents found for your account.</div>
          </div>
        ) : filteredDocs.length === 0 ? (
          <div>No documents found.</div>
        ) : (
          <ul className="divide-y">
              {filteredDocs.map(doc => (
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
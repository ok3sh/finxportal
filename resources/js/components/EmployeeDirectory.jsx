import { useEffect, useState } from 'react';

export default function EmployeeDirectory({ onClose }) {
  const [employees, setEmployees] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchEmployees();
  }, []);

  const fetchEmployees = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch('/api/employees', { credentials: 'include' });
      const data = await response.json();
      setEmployees(data);
    } catch (error) {
      console.error('Error fetching employees:', error);
      setError('Failed to fetch employees');
      setEmployees([]);
    } finally {
      setLoading(false);
    }
  };

  const filteredEmployees = employees.filter(employee =>
    employee.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    employee.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    employee.job_title?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    employee.department?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    employee.group_name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 backdrop-blur-sm">
      <div className="bg-white rounded-xl shadow-2xl p-6 w-full max-w-6xl h-5/6 flex flex-col relative">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-800">Employee Directory</h2>
            <p className="text-gray-600">Company-wide employee directory</p>
          </div>
          <button
            onClick={onClose}
            className="text-2xl text-gray-700 hover:text-black"
            aria-label="Close employee directory"
          >
            &times;
          </button>
        </div>

        {/* Search and Refresh */}
        <div className="mb-6">
          <div className="flex gap-4 items-center">
            <div className="flex-1 relative">
              <input
                type="text"
                placeholder="Search employees by name, email, title, department, or group..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none"
              />
            </div>
            <button
              onClick={fetchEmployees}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
            >
              Refresh
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-auto">
          {loading ? (
            <div className="flex items-center justify-center h-full">
              <div className="text-lg text-gray-600">Loading employees...</div>
            </div>
          ) : error ? (
            <div className="flex items-center justify-center h-full">
              <div className="text-center">
                <div className="text-lg text-red-600 mb-2">{error}</div>
                <button
                  onClick={fetchEmployees}
                  className="text-green-600 hover:text-green-700"
                >
                  Try again
                </button>
              </div>
            </div>
          ) : filteredEmployees.length === 0 ? (
            <div className="flex items-center justify-center h-full">
              <div className="text-center">
                <div className="text-lg text-gray-600 mb-2">
                  {searchTerm ? 'No employees found matching your search' : 'No employees found'}
                </div>
                {searchTerm && (
                  <button
                    onClick={() => setSearchTerm('')}
                    className="text-green-600 hover:text-green-700"
                  >
                    Clear search
                  </button>
                )}
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredEmployees.map((employee, index) => (
                <div key={employee.id || index} className="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                  <div className="flex items-start gap-3">
                    <div className="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                      {employee.name?.charAt(0).toUpperCase() || '?'}
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-gray-800 truncate">{employee.name}</h3>
                      {employee.job_title && (
                        <p className="text-sm text-gray-600 truncate">{employee.job_title}</p>
                      )}
                      {employee.department && (
                        <p className="text-sm text-gray-500 truncate">{employee.department}</p>
                      )}
                      {employee.office_location && (
                        <p className="text-xs text-gray-400 truncate">{employee.office_location}</p>
                      )}
                      {employee.group_name && (
                        <p className="text-xs text-green-600 font-medium mt-1">{employee.group_name}</p>
                      )}
                      <div className="flex gap-2 mt-2">
                        <a
                          href={`mailto:${employee.email}`}
                          className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 transition-colors"
                        >
                          Email
                        </a>
                        <button
                          onClick={() => navigator.clipboard.writeText(employee.email)}
                          className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200 transition-colors"
                        >
                          Copy
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="mt-4 pt-4 border-t bg-gray-50 text-center text-sm text-gray-600">
          Showing {filteredEmployees.length} of {employees.length} employees
        </div>
      </div>
    </div>
  );
} 
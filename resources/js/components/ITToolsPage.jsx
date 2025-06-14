import React, { useState, useEffect } from 'react';

// Asset request modal component
function AssetRequestModal({ isOpen, onClose, onSubmit }) {
  const [requestText, setRequestText] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!requestText.trim()) return;

    setIsSubmitting(true);
    try {
      await onSubmit(requestText);
      setRequestText('');
      onClose();
    } catch (error) {
      console.error('Failed to submit asset request:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4">Request Asset / IT Support</h3>
        <form onSubmit={handleSubmit}>
          <textarea
            value={requestText}
            onChange={(e) => setRequestText(e.target.value)}
            placeholder="Describe your asset request or IT issue..."
            className="w-full h-32 p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-[#115948] focus:border-transparent"
            required
          />
          <div className="flex gap-3 mt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 px-4 py-2 bg-[#115948] text-white rounded-lg hover:bg-[#0e4a3c] disabled:opacity-50"
              disabled={isSubmitting || !requestText.trim()}
            >
              {isSubmitting ? 'Submitting...' : 'Submit Request'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

// Decommission modal component
function DecommissionModal({ isOpen, onClose, onSubmit }) {
  const [serialId, setSerialId] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!serialId.trim()) return;

    setIsSubmitting(true);
    try {
      await onSubmit(serialId);
      setSerialId('');
      onClose();
    } catch (error) {
      console.error('Failed to submit decommission request:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 className="text-xl font-bold text-gray-800 mb-4">Initiate Decommission</h3>
        <form onSubmit={handleSubmit}>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Asset Serial ID
          </label>
          <input
            type="text"
            value={serialId}
            onChange={(e) => setSerialId(e.target.value)}
            placeholder="Enter asset serial ID..."
            className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115948] focus:border-transparent"
            required
          />
          <div className="flex gap-3 mt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
              disabled={isSubmitting || !serialId.trim()}
            >
              {isSubmitting ? 'Processing...' : 'Initiate Decommission'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

const ITToolsPage = () => {
    const [activeModal, setActiveModal] = useState(null);
    const [assets, setAssets] = useState([]);
    const [assetTypes, setAssetTypes] = useState([]);
    const [locations, setLocations] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedAssets, setSelectedAssets] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    
    // Form states
    const [assetForm, setAssetForm] = useState({
        type: '',
        ownership: 'SGPL',
        warranty: 'Under Warranty',
        warranty_start: '',
        warranty_end: '',
        serial_number: '',
        model: '',
        location: ''
    });

    useEffect(() => {
        fetchDropdownData();
        fetchEmployees();
    }, []);

    const fetchDropdownData = async () => {
        try {
            const [typesRes, locationsRes] = await Promise.all([
                fetch('/api/assets/types'),
                fetch('/api/assets/locations')
            ]);
            
            const types = await typesRes.json();
            const locations = await locationsRes.json();
            
            // Ensure we always have arrays, even if API fails
            setAssetTypes(Array.isArray(types) ? types : []);
            setLocations(Array.isArray(locations) ? locations : []);
        } catch (error) {
            console.error('Error fetching dropdown data:', error);
            // Set fallback data if API calls fail
            setAssetTypes([
                { id: 1, type: 'Laptop', keyword: 'LAP' },
                { id: 2, type: 'Desktop', keyword: 'DSK' },
                { id: 3, type: 'Mouse', keyword: 'MOU' },
                { id: 4, type: 'Keyboard', keyword: 'KEY' },
                { id: 5, type: 'Mobile', keyword: 'MOB' }
            ]);
            setLocations([
                { id: 1, unique_location: 'Office Floor 1' },
                { id: 2, unique_location: 'Office Floor 2' },
                { id: 3, unique_location: 'Office Floor 3' },
                { id: 4, unique_location: 'Remote Work' },
                { id: 5, unique_location: 'Conference Room A' },
                { id: 6, unique_location: 'Conference Room B' },
                { id: 7, unique_location: 'Storage Room' },
                { id: 8, unique_location: 'IT Department' }
            ]);
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/employees');
            const data = await response.json();
            setEmployees(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching employees:', error);
            setEmployees([]);
        }
    };

    const fetchAssets = async (filter = 'all') => {
        setLoading(true);
        try {
            const response = await fetch(`/api/assets?filter=${filter}`);
            const data = await response.json();
            
            if (response.ok) {
                setAssets(Array.isArray(data.assets) ? data.assets : []);
            } else {
                console.error('API Error:', data.error || data.message);
                setAssets([]);
                alert(data.error || 'Failed to fetch assets');
            }
        } catch (error) {
            console.error('Error fetching assets:', error);
            setAssets([]);
            alert('Failed to connect to server. Please check if the database tables exist.');
        } finally {
            setLoading(false);
        }
    };

    const handleAddAsset = async (e) => {
        e.preventDefault();
        setLoading(true);
        
        try {
            const response = await fetch('/api/assets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(assetForm)
            });

            const data = await response.json();
            
            if (response.ok) {
                alert(`Asset created successfully with tag: ${data.tag}`);
                setActiveModal(null);
                setAssetForm({
                    type: '',
                    ownership: 'SGPL',
                    warranty: 'Under Warranty',
                    warranty_start: '',
                    warranty_end: '',
                    serial_number: '',
                    model: '',
                    location: ''
                });
            } else {
                alert(data.error || 'Failed to create asset');
            }
        } catch (error) {
            console.error('Error creating asset:', error);
            alert('Failed to create asset');
        } finally {
            setLoading(false);
        }
    };

    const handleAllocateAsset = async (assetTag, userEmail) => {
        try {
            const response = await fetch('/api/assets/allocate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_tag: assetTag,
                    user_email: userEmail
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                alert('Asset allocated successfully');
                fetchAssets('inactive'); // Refresh inactive assets list
            } else {
                alert(data.error || 'Failed to allocate asset');
            }
        } catch (error) {
            console.error('Error allocating asset:', error);
            alert('Failed to allocate asset');
        }
    };

    const handleDeallocateAsset = async (assetTag) => {
        try {
            const response = await fetch('/api/assets/deallocate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_tag: assetTag
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                alert('Asset deallocated successfully');
                fetchAssets('active'); // Refresh active assets list
            } else {
                alert(data.error || 'Failed to deallocate asset');
            }
        } catch (error) {
            console.error('Error deallocating asset:', error);
            alert('Failed to deallocate asset');
        }
    };

    const handleReallocateAsset = async (assetTag, newUserEmail) => {
        try {
            const response = await fetch('/api/assets/reallocate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_tag: assetTag,
                    new_user_email: newUserEmail
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                alert('Asset reallocated successfully');
                fetchAssets('active'); // Refresh active assets list
            } else {
                alert(data.error || 'Failed to reallocate asset');
            }
        } catch (error) {
            console.error('Error reallocating asset:', error);
            alert('Failed to reallocate asset');
        }
    };

    const handleDecommissionAssets = async () => {
        if (selectedAssets.length === 0) {
            alert('Please select assets to decommission');
            return;
        }

        try {
            const response = await fetch('/api/assets/decommission', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_tags: selectedAssets
                })
            });

            const data = await response.json();
            
            if (response.ok) {
                alert('Assets decommissioned successfully');
                setSelectedAssets([]);
                fetchAssets('inactive'); // Refresh inactive assets list
            } else {
                alert(data.error || 'Failed to decommission assets');
            }
        } catch (error) {
            console.error('Error decommissioning assets:', error);
            alert('Failed to decommission assets');
        }
    };

    const filteredAssets = Array.isArray(assets) ? assets.filter(asset =>
        asset.tag?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        asset.model?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        asset.type?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        asset.serial_number?.toLowerCase().includes(searchTerm.toLowerCase())
    ) : [];

    const filteredEmployees = Array.isArray(employees) ? employees.filter(emp =>
        emp.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        emp.email?.toLowerCase().includes(searchTerm.toLowerCase())
    ) : [];

    const renderAssetTable = (showStatus) => (
        <div className="overflow-auto" style={{ maxHeight: '400px' }}>
            <table className="w-full text-sm">
                <thead className="sticky top-0 bg-gray-100">
                    <tr>
                        <th className="p-2 text-left">
                            <input
                                type="checkbox"
                                onChange={(e) => {
                                    if (e.target.checked) {
                                        setSelectedAssets(filteredAssets.map(a => a.tag));
                                    } else {
                                        setSelectedAssets([]);
                                    }
                                }}
                                checked={selectedAssets.length === filteredAssets.length && filteredAssets.length > 0}
                            />
                        </th>
                        <th className="p-2 text-left">Tag</th>
                        <th className="p-2 text-left">Type</th>
                        <th className="p-2 text-left">Model</th>
                        <th className="p-2 text-left">Serial</th>
                        <th className="p-2 text-left">Location</th>
                        {showStatus && <th className="p-2 text-left">Status</th>}
                        {showStatus && <th className="p-2 text-left">Allocated To</th>}
                    </tr>
                </thead>
                <tbody>
                    {filteredAssets.map((asset, index) => (
                        <tr key={asset.tag} className={index % 2 === 0 ? 'bg-gray-50' : 'bg-white'}>
                            <td className="p-2">
                                <input
                                    type="checkbox"
                                    checked={selectedAssets.includes(asset.tag)}
                                    onChange={(e) => {
                                        if (e.target.checked) {
                                            setSelectedAssets([...selectedAssets, asset.tag]);
                                        } else {
                                            setSelectedAssets(selectedAssets.filter(tag => tag !== asset.tag));
                                        }
                                    }}
                                />
                            </td>
                            <td className="p-2 font-medium">{asset.tag}</td>
                            <td className="p-2">{asset.type}</td>
                            <td className="p-2">{asset.model}</td>
                            <td className="p-2 text-xs">{asset.serial_number}</td>
                            <td className="p-2">{asset.location}</td>
                            {showStatus && (
                                <td className="p-2">
                                    <span className={`px-2 py-1 rounded text-xs ${
                                        asset.status === 'active' ? 'bg-green-100 text-green-800' :
                                        asset.status === 'inactive' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800'
                                    }`}>
                                        {asset.status}
                                    </span>
                                </td>
                            )}
                            {showStatus && (
                                <td className="p-2 text-xs">{asset.allocated_to_email || '-'}</td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );

    const renderUserDropdown = (onSelect, placeholder = "Select User") => (
        <div className="space-y-2">
            <input
                type="text"
                placeholder="Search users..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full p-2 border rounded"
            />
            <select
                onChange={(e) => onSelect(e.target.value)}
                className="w-full p-2 border rounded"
                defaultValue=""
            >
                <option value="">{placeholder}</option>
                {Array.isArray(filteredEmployees) && filteredEmployees.map(emp => (
                    <option key={emp.email} value={emp.email}>
                        {emp.name} ({emp.email})
                    </option>
                ))}
            </select>
        </div>
    );

    const renderModal = () => {
        if (!activeModal) return null;

        return (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div className="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-bold">{activeModal.title}</h2>
                        <button
                            onClick={() => {
                                setActiveModal(null);
                                setSelectedAssets([]);
                                setSearchTerm('');
                            }}
                            className="text-gray-500 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    {activeModal.type === 'add' && (
                        <form onSubmit={handleAddAsset} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Type *</label>
                                    <select
                                        value={assetForm.type}
                                        onChange={(e) => setAssetForm({...assetForm, type: e.target.value})}
                                        required
                                        className="w-full p-2 border rounded"
                                    >
                                        <option value="">Select Type</option>
                                        {Array.isArray(assetTypes) && assetTypes.map(type => (
                                            <option key={type.id} value={type.type}>{type.type}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Ownership *</label>
                                    <select
                                        value={assetForm.ownership}
                                        onChange={(e) => setAssetForm({...assetForm, ownership: e.target.value})}
                                        required
                                        className="w-full p-2 border rounded"
                                    >
                                        <option value="SGPL">SGPL</option>
                                        <option value="Rental">Rental</option>
                                        <option value="BYOD">BYOD</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Warranty *</label>
                                    <select
                                        value={assetForm.warranty}
                                        onChange={(e) => setAssetForm({...assetForm, warranty: e.target.value})}
                                        required
                                        className="w-full p-2 border rounded"
                                    >
                                        <option value="Under Warranty">Under Warranty</option>
                                        <option value="NA">NA</option>
                                        <option value="Out of Warranty">Out of Warranty</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Location *</label>
                                    <select
                                        value={assetForm.location}
                                        onChange={(e) => setAssetForm({...assetForm, location: e.target.value})}
                                        required
                                        className="w-full p-2 border rounded"
                                    >
                                        <option value="">Select Location</option>
                                        {Array.isArray(locations) && locations.map(loc => (
                                            <option key={loc.id} value={loc.unique_location}>{loc.unique_location}</option>
                                        ))}
                                    </select>
                                </div>
                                {assetForm.warranty === 'Under Warranty' && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium mb-1">Warranty Start</label>
                                            <input
                                                type="date"
                                                value={assetForm.warranty_start}
                                                onChange={(e) => setAssetForm({...assetForm, warranty_start: e.target.value})}
                                                className="w-full p-2 border rounded"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium mb-1">Warranty End</label>
                                            <input
                                                type="date"
                                                value={assetForm.warranty_end}
                                                onChange={(e) => setAssetForm({...assetForm, warranty_end: e.target.value})}
                                                className="w-full p-2 border rounded"
                                            />
                                        </div>
                                    </>
                                )}
                                <div>
                                    <label className="block text-sm font-medium mb-1">Serial Number * (0-30 chars)</label>
                                    <input
                                        type="text"
                                        value={assetForm.serial_number}
                                        onChange={(e) => setAssetForm({...assetForm, serial_number: e.target.value})}
                                        maxLength={30}
                                        required
                                        placeholder="Usually found under your device"
                                        className="w-full p-2 border rounded"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Model * (0-50 chars)</label>
                                    <input
                                        type="text"
                                        value={assetForm.model}
                                        onChange={(e) => setAssetForm({...assetForm, model: e.target.value})}
                                        maxLength={50}
                                        required
                                        className="w-full p-2 border rounded"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 disabled:opacity-50"
                            >
                                {loading ? 'Adding...' : 'Add Asset'}
                            </button>
                        </form>
                    )}

                    {activeModal.type === 'decommission' && (
                        <div className="space-y-4">
                            <div className="mb-4">
                                <input
                                    type="text"
                                    placeholder="Search assets..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full p-2 border rounded"
                                />
                            </div>
                            {renderAssetTable(false)}
                            <button
                                onClick={handleDecommissionAssets}
                                disabled={selectedAssets.length === 0}
                                className="w-full bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 disabled:opacity-50"
                            >
                                Decommission Selected Assets ({selectedAssets.length})
                            </button>
                        </div>
                    )}

                    {activeModal.type === 'allocate' && (
                        <div className="space-y-4">
                            <div className="mb-4">
                                <input
                                    type="text"
                                    placeholder="Search assets..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full p-2 border rounded"
                                />
                            </div>
                            {renderAssetTable(false)}
                            {selectedAssets.length === 1 && (
                                <div className="mt-4 p-4 bg-gray-50 rounded">
                                    <h3 className="font-medium mb-2">Allocate {selectedAssets[0]} to:</h3>
                                    {renderUserDropdown((email) => {
                                        if (email) {
                                            handleAllocateAsset(selectedAssets[0], email);
                                            setActiveModal(null);
                                            setSelectedAssets([]);
                                            setSearchTerm('');
                                        }
                                    }, "Select user to allocate")}
                                </div>
                            )}
                            {selectedAssets.length > 1 && (
                                <div className="mt-4 p-4 bg-yellow-50 rounded">
                                    <p className="text-yellow-800">Please select only one asset for allocation.</p>
                                </div>
                            )}
                        </div>
                    )}

                    {activeModal.type === 'deallocate' && (
                        <div className="space-y-4">
                            <div className="mb-4">
                                <input
                                    type="text"
                                    placeholder="Search assets..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full p-2 border rounded"
                                />
                            </div>
                            {renderAssetTable(true)}
                            {selectedAssets.length === 1 && (
                                <div className="mt-4 p-4 bg-gray-50 rounded space-y-2">
                                    <h3 className="font-medium">Actions for {selectedAssets[0]}:</h3>
                                    <div className="space-y-2">
                                        <div>
                                            <h4 className="text-sm font-medium mb-2">Reallocate to another user:</h4>
                                            {renderUserDropdown((email) => {
                                                if (email) {
                                                    handleReallocateAsset(selectedAssets[0], email);
                                                    setActiveModal(null);
                                                    setSelectedAssets([]);
                                                    setSearchTerm('');
                                                }
                                            }, "Select new user")}
                                        </div>
                                        <button
                                            onClick={() => {
                                                handleDeallocateAsset(selectedAssets[0]);
                                                setActiveModal(null);
                                                setSelectedAssets([]);
                                            }}
                                            className="w-full bg-yellow-600 text-white py-2 px-4 rounded hover:bg-yellow-700"
                                        >
                                            Deallocate Asset
                                        </button>
                                    </div>
                                </div>
                            )}
                            {selectedAssets.length > 1 && (
                                <div className="mt-4 p-4 bg-yellow-50 rounded">
                                    <p className="text-yellow-800">Please select only one asset for deallocation/reallocation.</p>
                                </div>
                            )}
                        </div>
                    )}

                    {activeModal.type === 'all-assets' && (
                        <div className="space-y-4">
                            <div className="mb-4">
                                <input
                                    type="text"
                                    placeholder="Search assets..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="w-full p-2 border rounded"
                                />
                            </div>
                            {renderAssetTable(true)}
                            <div className="text-sm text-gray-600">
                                Total Assets: {filteredAssets.length}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    return (
        <div className="bg-white rounded-2xl shadow-lg p-6">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                        <span className="text-white font-bold">🛠️</span>
                    </div>
                    <h1 className="text-2xl font-bold text-gray-800">IT TOOLS</h1>
                </div>
            </div>

            {/* Action Buttons Grid */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <button
                    onClick={() => {
                        fetchAssets('all');
                        setActiveModal({ type: 'all-assets', title: 'All Assets' });
                    }}
                    className="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg transition-colors"
                >
                    <div className="text-center">
                        <div className="text-2xl mb-2">📊</div>
                        <div className="font-medium">All Assets</div>
                    </div>
                </button>

                <button
                    onClick={() => setActiveModal({ type: 'add', title: 'Add New Asset' })}
                    className="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg transition-colors"
                >
                    <div className="text-center">
                        <div className="text-2xl mb-2">➕</div>
                        <div className="font-medium">Add Asset</div>
                    </div>
                </button>

                <button
                    onClick={() => {
                        fetchAssets('inactive');
                        setActiveModal({ type: 'decommission', title: 'Decommission Assets' });
                    }}
                    className="bg-red-600 hover:bg-red-700 text-white p-4 rounded-lg transition-colors"
                >
                    <div className="text-center">
                        <div className="text-2xl mb-2">🗑️</div>
                        <div className="font-medium">Decommission</div>
                    </div>
                </button>

                <button
                    onClick={() => {
                        fetchAssets('inactive');
                        setActiveModal({ type: 'allocate', title: 'Allocate Assets' });
                    }}
                    className="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg transition-colors"
                >
                    <div className="text-center">
                        <div className="text-2xl mb-2">📤</div>
                        <div className="font-medium">Allocation</div>
                    </div>
                </button>

                <button
                    onClick={() => {
                        fetchAssets('active');
                        setActiveModal({ type: 'deallocate', title: 'Deallocate Assets' });
                    }}
                    className="bg-orange-600 hover:bg-orange-700 text-white p-4 rounded-lg transition-colors"
                >
                    <div className="text-center">
                        <div className="text-2xl mb-2">📥</div>
                        <div className="font-medium">Deallocate</div>
                    </div>
                </button>
            </div>

            {/* Loading Indicator */}
            {loading && (
                <div className="mt-6 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    <p className="mt-2 text-gray-600">Loading...</p>
                </div>
            )}

            {/* Modal */}
            {renderModal()}
        </div>
    );
};

export default ITToolsPage; 
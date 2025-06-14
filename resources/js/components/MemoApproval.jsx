import React, { useState, useEffect } from 'react';
import axios from 'axios';

const MemoApproval = ({ onClose }) => {
  // Main state management
  const [activeTab, setActiveTab] = useState('raise'); // 'raise' or 'approve'
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Memo raising states
  const [description, setDescription] = useState('');
  const [document, setDocument] = useState(null);
  const [dragActive, setDragActive] = useState(false);
  const [groupMembers, setGroupMembers] = useState([]);
  const [selectedApprovers, setSelectedApprovers] = useState([]);
  const [submitting, setSubmitting] = useState(false);

  // Approval states
  const [approvals, setApprovals] = useState([]);
  const [processingId, setProcessingId] = useState(null);
  const [declineModal, setDeclineModal] = useState({ show: false, approval: null });
  const [declineReason, setDeclineReason] = useState('');

  useEffect(() => {
    fetchGroupMembers();
    if (activeTab === 'approve') {
      fetchPendingApprovals();
    }
  }, [activeTab]);

  // ====== MEMO RAISING FUNCTIONS ======

  const fetchGroupMembers = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('🔍 Fetching group members from Microsoft Graph...');
      const response = await axios.get('/api/group-members');
      
      // Group members by their group name and priority
      const groupedMembers = {};
      response.data.forEach(member => {
        const groupName = member.group_name;
        if (!groupedMembers[groupName]) {
          groupedMembers[groupName] = {
            name: groupName,
            priority: member.group_priority || 999,
            members: []
          };
        }
        groupedMembers[groupName].members.push(member);
      });

      // Convert to array and sort by priority (lower number = higher priority)
      const sortedGroups = Object.values(groupedMembers).sort((a, b) => a.priority - b.priority);
      
      setGroupMembers(sortedGroups);
      console.log(`📋 Loaded ${sortedGroups.length} groups with hierarchy`);
      
    } catch (err) {
      console.error('Error fetching group members:', err);
      setError('Failed to load group members. Please try again.');
      setGroupMembers([]);
    } finally {
      setLoading(false);
    }
  };

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const file = e.dataTransfer.files[0];
      setDocument(file);
    }
  };

  const handleFileSelect = (e) => {
    if (e.target.files && e.target.files[0]) {
      setDocument(e.target.files[0]);
    }
  };

  const removeDocument = () => {
    setDocument(null);
  };

  const handleGroupToggle = (groupName) => {
    console.log('🔄 Group toggle clicked:', groupName);
    console.log('📋 Current selected approvers:', selectedApprovers);
    
    setSelectedApprovers(prev => {
      const newSelection = prev.includes(groupName)
        ? prev.filter(name => name !== groupName)
        : [...prev, groupName];
      
      console.log('✅ New selection:', newSelection);
      return newSelection;
    });
  };

  const handleSubmitMemo = async () => {
    if (!description.trim()) {
      alert('Please enter a memo description');
      return;
    }
    if (!document) {
      alert('Please upload a document');
      return;
    }
    if (selectedApprovers.length === 0) {
      alert('Please select at least one approver group');
      return;
    }

    try {
      setSubmitting(true);
      
      const formData = new FormData();
      formData.append('description', description);
      formData.append('document', document);
      
      // Send selected group names as approvers array
      selectedApprovers.forEach((groupName, index) => {
        formData.append(`approvers[${index}]`, groupName);
      });

      console.log('📤 Submitting memo with approvers:', selectedApprovers);

      const response = await axios.post('/api/memos', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      console.log('✅ Memo submitted successfully:', response.data);
      
      // Reset form
      setDescription('');
      setDocument(null);
      setSelectedApprovers([]);
      
      alert(`Memo raised successfully! Approval required from: ${selectedApprovers.join(', ')}`);
      
    } catch (err) {
      console.error('Error submitting memo:', err);
      const errorMessage = err.response?.data?.message || err.message || 'Failed to submit memo';
      alert(`Error: ${errorMessage}`);
    } finally {
      setSubmitting(false);
    }
  };

  // ====== APPROVAL FUNCTIONS ======

  const fetchPendingApprovals = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await axios.get('/api/my-approvals');
      setApprovals(response.data);
      console.log(`📋 Found ${response.data.length} pending approvals`);
      
    } catch (err) {
      console.error('Error fetching approvals:', err);
      setError('Failed to load pending approvals. Please try again.');
      setApprovals([]);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (approvalId) => {
    try {
      setProcessingId(approvalId);
      
      const response = await axios.post(`/api/approvals/${approvalId}/approve`);
      
      // Remove approved item from list
      setApprovals(prev => prev.filter(approval => approval.id !== approvalId));
      
      alert(`Approved successfully by ${response.data.approved_by} for ${response.data.group}`);
      
    } catch (err) {
      console.error('Error approving memo:', err);
      const errorMessage = err.response?.data?.error || err.message || 'Failed to approve memo';
      alert(`Error: ${errorMessage}`);
    } finally {
      setProcessingId(null);
    }
  };

  const handleDeclineSubmit = async () => {
    if (!declineReason.trim()) {
      alert('Please provide a reason for declining');
      return;
    }

    try {
      setProcessingId(declineModal.approval.id);
      
      const response = await axios.post(`/api/approvals/${declineModal.approval.id}/decline`, {
        comment: declineReason
      });

      // Remove declined item from list
      setApprovals(prev => prev.filter(approval => approval.id !== declineModal.approval.id));
      
      // Close modal and reset
      setDeclineModal({ show: false, approval: null });
      setDeclineReason('');
      
      alert(`Declined successfully by ${response.data.declined_by} for ${response.data.group}. ${response.data.email_sent ? 'Notification email has been sent.' : ''}`);
      
    } catch (err) {
      console.error('Error declining memo:', err);
      const errorMessage = err.response?.data?.error || err.message || 'Failed to decline memo';
      alert(`Error: ${errorMessage}`);
    } finally {
      setProcessingId(null);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const viewDocument = (documentPath) => {
    if (documentPath) {
      window.open(`/storage/${documentPath}`, '_blank');
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  // ====== RENDER FUNCTIONS ======

  const renderRaiseTicketTab = () => (
    <div className="space-y-6">
      {/* Description Input */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Memo Description *
        </label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder="Enter a brief description of your memo..."
          className="w-full p-3 border border-gray-300 rounded-lg resize-none h-24 focus:outline-none focus:ring-2 focus:ring-[#115948] focus:border-transparent"
          maxLength={1000}
        />
        <div className="text-right text-sm text-gray-500 mt-1">
          {description.length}/1000 characters
        </div>
      </div>

      {/* Document Upload */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Document Upload *
        </label>
        <div
          className={`relative border-2 border-dashed rounded-lg p-6 transition-colors ${
            dragActive 
              ? 'border-[#115948] bg-green-50' 
              : document 
                ? 'border-green-500 bg-green-50'
                : 'border-gray-300 hover:border-[#115948]'
          }`}
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          onDrop={handleDrop}
        >
          {document ? (
            <div className="text-center">
              <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="text-sm text-gray-700 font-medium">{document.name}</div>
              <div className="text-xs text-gray-500">{formatFileSize(document.size)}</div>
              <button
                onClick={removeDocument}
                className="mt-2 text-red-600 hover:text-red-800 text-sm underline"
              >
                Remove file
              </button>
            </div>
          ) : (
            <div className="text-center">
              <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <div className="text-sm text-gray-700 mb-2">Drag and drop your file here, or</div>
              <label className="inline-flex items-center px-4 py-2 bg-[#115948] text-white rounded-lg hover:bg-[#177761] cursor-pointer transition-colors">
                <span>Browse Files</span>
                <input
                  type="file"
                  className="hidden"
                  onChange={handleFileSelect}
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                />
              </label>
              <div className="text-xs text-gray-500 mt-2">
                Supported: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT (Max 10MB)
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Approver Selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Select Approver Groups * 
          <span className="text-xs text-gray-500 ml-2">(Multiple selection allowed)</span>
        </label>
        
        {loading ? (
          <div className="text-center py-8">
            <div className="text-[#115948]">Loading groups...</div>
          </div>
        ) : error ? (
          <div className="text-center py-8">
            <div className="text-red-600 mb-2">{error}</div>
            <button 
              onClick={fetchGroupMembers}
              className="text-[#115948] hover:text-[#177761] underline"
            >
              Retry
            </button>
          </div>
        ) : (
          <div className="space-y-3 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-4">
            {groupMembers.length === 0 ? (
              <div className="text-center py-4 text-gray-500">
                No groups available. Please check your Microsoft Graph connection.
              </div>
            ) : (
              groupMembers.map((group) => (
                <div 
                  key={group.name} 
                  className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                    selectedApprovers.includes(group.name)
                      ? 'border-[#115948] bg-green-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    handleGroupToggle(group.name);
                  }}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <input
                        type="checkbox"
                        checked={selectedApprovers.includes(group.name)}
                        onChange={(e) => {
                          e.stopPropagation();
                          handleGroupToggle(group.name);
                        }}
                        className="w-4 h-4 text-[#115948] bg-gray-100 border-gray-300 rounded focus:ring-[#115948] focus:ring-2 pointer-events-auto"
                      />
                      <div>
                        <div className="font-medium text-gray-900">{group.name}</div>
                        <div className="text-sm text-gray-500">{group.members.length} members</div>
                      </div>
                    </div>
                    <div className="flex items-center space-x-2">
                      <span className={`px-2 py-1 text-xs rounded-full ${
                        group.priority === 1 ? 'bg-red-100 text-red-800' :
                        group.priority === 2 ? 'bg-orange-100 text-orange-800' :
                        group.priority === 3 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        Priority {group.priority}
                      </span>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        )}
        
        {selectedApprovers.length > 0 && (
          <div className="mt-3">
            <div className="text-sm text-gray-700 mb-2">Selected approvers:</div>
            <div className="flex flex-wrap gap-2">
              {selectedApprovers.map((groupName) => (
                <span 
                  key={groupName}
                  className="px-3 py-1 bg-[#115948] text-white text-sm rounded-full flex items-center gap-2"
                >
                  {groupName}
                  <button
                    onClick={() => handleGroupToggle(groupName)}
                    className="hover:bg-[#177761] rounded-full p-0.5"
                  >
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Submit Button */}
      <div className="flex justify-end pt-4">
        <button
          onClick={handleSubmitMemo}
          disabled={submitting || !description.trim() || !document || selectedApprovers.length === 0}
          className="bg-[#115948] hover:bg-[#177761] disabled:bg-gray-400 text-white px-8 py-3 rounded-lg transition-colors flex items-center gap-2"
        >
          {submitting ? (
            <>
              <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Submitting...
            </>
          ) : (
            <>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              Raise Ticket
            </>
          )}
        </button>
      </div>
    </div>
  );

  const renderApprovalTab = () => (
    <div className="space-y-4">
      {loading ? (
        <div className="text-center py-16">
          <div className="text-[#115948] text-lg">Loading pending approvals...</div>
        </div>
      ) : error ? (
        <div className="text-center py-16">
          <div className="text-red-600 text-lg mb-4">Error loading approvals</div>
          <div className="text-gray-600 mb-4">{error}</div>
          <button 
            onClick={fetchPendingApprovals}
            className="bg-[#115948] hover:bg-[#177761] text-white px-6 py-2 rounded-lg transition-colors"
          >
            Retry
          </button>
        </div>
      ) : approvals.length === 0 ? (
        <div className="text-center py-16">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-[#115948]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div className="text-[#115948] text-xl font-semibold mb-2">All Caught Up!</div>
          <div className="text-gray-600">No memos require your approval at this time.</div>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#115948]">Pending Approvals ({approvals.length})</h3>
            <button
              onClick={fetchPendingApprovals}
              disabled={loading}
              className="bg-[#177761] hover:bg-[#115948] disabled:bg-[#0d4034] text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
          </div>
          
          {approvals.map((approval) => (
            <div key={approval.id} className="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow">
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex-1">
                    <h4 className="text-lg font-semibold text-[#115948] mb-2">
                      {approval.memo.description}
                    </h4>
                    <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                      <div>
                        <span className="font-medium text-gray-700">Submitted by:</span> {approval.memo.raiser?.name || 'Unknown User'}
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Email:</span> {approval.memo.raiser?.email || 'unknown@company.com'}
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Date:</span> {formatDate(approval.memo.issued_on)}
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Required Group:</span> 
                        <span className="ml-2 px-2 py-1 bg-[#115948] text-white text-xs rounded-full">
                          {approval.required_group}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Document Link */}
                {approval.memo.document_path && (
                  <div className="mb-4">
                    <button
                      onClick={() => viewDocument(approval.memo.document_path)}
                      className="inline-flex items-center text-[#115948] hover:text-[#177761] font-medium"
                    >
                      <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      View Document
                    </button>
                  </div>
                )}

                {/* Action Buttons */}
                <div className="flex items-center gap-3 pt-4 border-t border-gray-100">
                  <button
                    onClick={() => handleApprove(approval.id)}
                    disabled={processingId === approval.id}
                    className="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    {processingId === approval.id ? 'Approving...' : 'Approve'}
                  </button>
                  
                  <button
                    onClick={() => setDeclineModal({ show: true, approval })}
                    disabled={processingId === approval.id}
                    className="bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors flex items-center gap-2"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Decline
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  return (
    <div className="h-full bg-white overflow-y-auto">
      {/* Header */}
      <div className="bg-[#115948] text-white p-6 rounded-b-2xl mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Memo Approval System</h1>
            <p className="text-green-200 mt-1">Raise tickets and manage approvals</p>
          </div>
          <button
            onClick={onClose}
            className="bg-white text-[#115948] px-4 py-2 rounded-lg transition-colors hover:bg-gray-100"
          >
            Back to Dashboard
          </button>
        </div>

        {/* Tab Navigation */}
        <div className="flex space-x-1 mt-6">
          <button
            onClick={() => setActiveTab('raise')}
            className={`px-6 py-2 rounded-lg transition-colors ${
              activeTab === 'raise'
                ? 'bg-white text-[#115948]'
                : 'bg-[#177761] text-white hover:bg-white hover:text-[#115948]'
            }`}
          >
            Raise Ticket
          </button>
          <button
            onClick={() => setActiveTab('approve')}
            className={`px-6 py-2 rounded-lg transition-colors ${
              activeTab === 'approve'
                ? 'bg-white text-[#115948]'
                : 'bg-[#177761] text-white hover:bg-white hover:text-[#115948]'
            }`}
          >
            My Approvals
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="px-6 pb-6">
        {activeTab === 'raise' ? renderRaiseTicketTab() : renderApprovalTab()}
      </div>

      {/* Decline Modal */}
      {declineModal.show && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 w-full max-w-md mx-4">
            <h3 className="text-lg font-semibold text-[#115948] mb-4">Decline Memo</h3>
            <p className="text-gray-600 mb-4">
              Please provide a reason for declining this memo. The submitter will be notified via email with their original document attached.
            </p>
            <textarea
              value={declineReason}
              onChange={(e) => setDeclineReason(e.target.value)}
              placeholder="Enter decline reason..."
              className="w-full p-3 border border-gray-300 rounded-lg resize-none h-24 focus:outline-none focus:ring-2 focus:ring-[#115948] focus:border-transparent"
            />
            <div className="flex items-center justify-end gap-3 mt-4">
              <button
                onClick={() => {
                  setDeclineModal({ show: false, approval: null });
                  setDeclineReason('');
                }}
                className="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleDeclineSubmit}
                disabled={!declineReason.trim() || processingId}
                className="bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors"
              >
                {processingId ? 'Declining...' : 'Decline & Send Email'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default MemoApproval; 
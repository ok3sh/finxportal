import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const HRAdminTools = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('create-job');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  // Data states
  const [jobs, setJobs] = useState([]);
  const [candidates, setCandidates] = useState([]);
  const [candidateSources, setCandidateSources] = useState([]);
  const [candidateSkills, setCandidateSkills] = useState([]);
  const [availableCandidates, setAvailableCandidates] = useState([]);
  const [candidatesForApproval, setCandidatesForApproval] = useState([]);
  const [verifiedCandidates, setVerifiedCandidates] = useState([]);
  const [activeEmployees, setActiveEmployees] = useState([]);

  // Form states
  const [jobForm, setJobForm] = useState({
    job_title: '', department: '', location: '', hiring_manager: '',
    job_description: '', experience_requirements: '', education_requirements: '',
    number_of_openings: 1, salary_min: '', salary_max: ''
  });

  const [candidateForm, setCandidateForm] = useState({
    name: '', email: '', phone: '', source_id: '', skills: [], notes: '', resume: null
  });

  const [assignmentForm, setAssignmentForm] = useState({
    candidate_ids: [], job_id: '', assignment_status: 'Applied', email_content: ''
  });

  const [interviewForm, setInterviewForm] = useState({
    candidate_id: '', job_id: '', interviewer_emails: [''], interview_datetime: '',
    mode: 'Video', meeting_link_or_location: '', notes: ''
  });

  const [offerForm, setOfferForm] = useState({
    candidate_id: '', job_id: '', subject_line: '', email_content: '', offer_document: null
  });

  const [onboardingForm, setOnboardingForm] = useState({
    candidate_id: '', job_id: '', start_date: '', manager_email: ''
  });

  const [resignationForm, setResignationForm] = useState({
    employee_ids: [], last_working_day: '', resignation_reason: ''
  });

  const handleBackToDashboard = () => {
    navigate('/');
  };

  // Load initial data
  useEffect(() => {
    loadInitialData();
  }, []);

  // Load tab-specific data when activeTab changes
  useEffect(() => {
    switch (activeTab) {
      case 'assign-job':
        loadAvailableCandidates();
        break;
      case 'approve-candidate':
        loadCandidatesForApproval();
        break;
      case 'schedule-interview':
        loadVerifiedCandidates();
        break;
      case 'send-offer':
        loadVerifiedCandidates();
        break;
      case 'start-onboarding':
        loadVerifiedCandidates();
        break;
      case 'mark-resignation':
        loadActiveEmployees();
        break;
    }
  }, [activeTab]);

  const loadInitialData = async () => {
    try {
      const [sourcesRes, skillsRes, jobsRes] = await Promise.all([
        fetch('/api/hr/candidate-sources'),
        fetch('/api/hr/candidate-skills'), 
        fetch('/api/hr/jobs')
      ]);

      if (sourcesRes.ok) setCandidateSources(await sourcesRes.json());
      if (skillsRes.ok) setCandidateSkills(await skillsRes.json());
      if (jobsRes.ok) setJobs(await jobsRes.json());
    } catch (error) {
      console.error('Error loading initial data:', error);
    }
  };

  const showMessage = (msg, isError = false) => {
    setMessage({ text: msg, type: isError ? 'error' : 'success' });
    setTimeout(() => setMessage(''), 5000);
  };

  // 1. Create Job
  const handleCreateJob = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/jobs', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(jobForm)
      });

      if (response.ok) {
        showMessage('Job created successfully!');
        setJobForm({
          job_title: '', department: '', location: '', hiring_manager: '',
          job_description: '', experience_requirements: '', education_requirements: '',
          number_of_openings: 1, salary_min: '', salary_max: ''
        });
        loadInitialData(); // Refresh jobs list
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to create job', true);
      }
    } catch (error) {
      showMessage('Error creating job', true);
    }
    setLoading(false);
  };

  // 2. Add Candidate
  const handleAddCandidate = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(candidateForm).forEach(key => {
        if (key === 'skills') {
          candidateForm[key].forEach((skill, index) => {
            formData.append(`skills[${index}]`, skill);
          });
        } else if (key === 'resume' && candidateForm[key]) {
          formData.append(key, candidateForm[key]);
        } else {
          formData.append(key, candidateForm[key]);
        }
      });

      const response = await fetch('/api/hr/candidates', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        showMessage('Candidate added successfully!');
        setCandidateForm({
          name: '', email: '', phone: '', source_id: '', skills: [], notes: '', resume: null
        });
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to add candidate', true);
      }
    } catch (error) {
      showMessage('Error adding candidate', true);
    }
    setLoading(false);
  };

  // 3. Assign to Job
  const handleAssignToJob = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/assign-to-job', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(assignmentForm)
      });

      if (response.ok) {
        const result = await response.json();
        showMessage(`${result.assigned_count} candidates assigned successfully!`);
        setAssignmentForm({
          candidate_ids: [], job_id: '', assignment_status: 'Applied', email_content: ''
        });
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to assign candidates', true);
      }
    } catch (error) {
      showMessage('Error assigning candidates', true);
    }
    setLoading(false);
  };

  // 4. Approve Candidate
  const handleApproveCandidate = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/approve-candidate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ candidate_job_ids: candidatesForApproval.filter(c => c.selected).map(c => c.id) })
      });

      if (response.ok) {
        const result = await response.json();
        showMessage(`${result.verified_count} candidates approved successfully!`);
        loadCandidatesForApproval();
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to approve candidates', true);
      }
    } catch (error) {
      showMessage('Error approving candidates', true);
    }
    setLoading(false);
  };

  // 5. Schedule Interview
  const handleScheduleInterview = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/schedule-interview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(interviewForm)
      });

      if (response.ok) {
        showMessage('Interview scheduled successfully!');
        setInterviewForm({
          candidate_id: '', job_id: '', interviewer_emails: [''], interview_datetime: '',
          mode: 'Video', meeting_link_or_location: '', notes: ''
        });
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to schedule interview', true);
      }
    } catch (error) {
      showMessage('Error scheduling interview', true);
    }
    setLoading(false);
  };

  // 6. Send Offer
  const handleSendOffer = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const formData = new FormData();
      Object.keys(offerForm).forEach(key => {
        if (key === 'offer_document' && offerForm[key]) {
          formData.append(key, offerForm[key]);
        } else {
          formData.append(key, offerForm[key]);
        }
      });

      const response = await fetch('/api/hr/send-offer', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        showMessage('Offer sent successfully!');
        setOfferForm({
          candidate_id: '', job_id: '', subject_line: '', email_content: '', offer_document: null
        });
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to send offer', true);
      }
    } catch (error) {
      showMessage('Error sending offer', true);
    }
    setLoading(false);
  };

  // 7. Start Onboarding
  const handleStartOnboarding = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/start-onboarding', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(onboardingForm)
      });

      if (response.ok) {
        const result = await response.json();
        showMessage(`Onboarding started! Employee email: ${result.employee_email}`);
        setOnboardingForm({
          candidate_id: '', job_id: '', start_date: '', manager_email: ''
        });
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to start onboarding', true);
      }
    } catch (error) {
      showMessage('Error starting onboarding', true);
    }
    setLoading(false);
  };

  // 8. Mark Resignation
  const handleMarkResignation = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const response = await fetch('/api/hr/mark-resignation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(resignationForm)
      });

      if (response.ok) {
        const result = await response.json();
        showMessage(`${result.resigned_count} employees marked as resigned successfully!`);
        setResignationForm({
          employee_ids: [], last_working_day: '', resignation_reason: ''
        });
        loadActiveEmployees();
      } else {
        const error = await response.json();
        showMessage(error.message || 'Failed to mark resignation', true);
      }
    } catch (error) {
      showMessage('Error marking resignation', true);
    }
    setLoading(false);
  };

  // Load data for different tabs
  const loadAvailableCandidates = async (jobId = null) => {
    try {
      const url = jobId ? `/api/hr/available-candidates?job_id=${jobId}` : '/api/hr/available-candidates';
      const response = await fetch(url);
      if (response.ok) {
        setAvailableCandidates(await response.json());
      }
    } catch (error) {
      console.error('Error loading available candidates:', error);
    }
  };

  const loadCandidatesForApproval = async () => {
    try {
      const response = await fetch('/api/hr/candidates-for-approval');
      if (response.ok) {
        const data = await response.json();
        setCandidatesForApproval(data.map(item => ({ ...item, selected: false })));
      }
    } catch (error) {
      console.error('Error loading candidates for approval:', error);
    }
  };

  const loadVerifiedCandidates = async () => {
    try {
      const response = await fetch('/api/hr/verified-candidates');
      if (response.ok) {
        setVerifiedCandidates(await response.json());
      }
    } catch (error) {
      console.error('Error loading verified candidates:', error);
    }
  };

  const loadActiveEmployees = async () => {
    try {
      const response = await fetch('/api/hr/active-employees');
      if (response.ok) {
        const data = await response.json();
        setActiveEmployees(data.map(emp => ({ ...emp, selected: false })));
      }
    } catch (error) {
      console.error('Error loading active employees:', error);
    }
  };

  const tabs = [
    { id: 'create-job', name: '1. Create Job', icon: '💼' },
    { id: 'add-candidate', name: '2. Add Candidate', icon: '👤' },
    { id: 'assign-job', name: '3. Assign to Job', icon: '🔗' },
    { id: 'approve-candidate', name: '4. Candidate Approval', icon: '✅' },
    { id: 'schedule-interview', name: '5. Schedule Interview', icon: '📅' },
    { id: 'send-offer', name: '6. Send Offer', icon: '📄' },
    { id: 'start-onboarding', name: '7. Start Onboarding', icon: '🎉' },
    { id: 'mark-resignation', name: '8. Mark Resignation', icon: '👋' }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <button
                onClick={handleBackToDashboard}
                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                ← Back to Dashboard
              </button>
              <h1 className="ml-6 text-3xl font-bold text-gray-900">HR Admin Tools</h1>
            </div>
          </div>
        </div>
      </div>

      {/* Message Display */}
      {message && (
        <div className={`max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4`}>
          <div className={`p-4 rounded-md ${message.type === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'}`}>
            {message.text}
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 overflow-x-auto">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <span className="mr-2">{tab.icon}</span>
                {tab.name}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="bg-white rounded-lg shadow p-6">
          {/* Tab Content */}
          {activeTab === 'create-job' && <CreateJobForm />}
          {activeTab === 'add-candidate' && <AddCandidateForm />}
          {activeTab === 'assign-job' && <AssignToJobForm />}
          {activeTab === 'approve-candidate' && <ApproveCandidateForm />}
          {activeTab === 'schedule-interview' && <ScheduleInterviewForm />}
          {activeTab === 'send-offer' && <SendOfferForm />}
          {activeTab === 'start-onboarding' && <StartOnboardingForm />}
          {activeTab === 'mark-resignation' && <MarkResignationForm />}
        </div>
      </div>
    </div>
  );

  // Component functions will be defined separately due to length
  function CreateJobForm() {
    return (
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">Create New Job Opening</h3>
        <form onSubmit={handleCreateJob} className="space-y-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-gray-700">Job Title</label>
              <input
                type="text"
                required
                value={jobForm.job_title}
                onChange={(e) => setJobForm({...jobForm, job_title: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Department</label>
              <input
                type="text"
                required
                value={jobForm.department}
                onChange={(e) => setJobForm({...jobForm, department: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Location</label>
              <input
                type="text"
                required
                value={jobForm.location}
                onChange={(e) => setJobForm({...jobForm, location: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Hiring Manager</label>
              <input
                type="text"
                required
                value={jobForm.hiring_manager}
                onChange={(e) => setJobForm({...jobForm, hiring_manager: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-700">Job Description</label>
            <textarea
              required
              rows={4}
              value={jobForm.job_description}
              onChange={(e) => setJobForm({...jobForm, job_description: e.target.value})}
              className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
              <label className="block text-sm font-medium text-gray-700">Number of Openings</label>
              <input
                type="number"
                min="1"
                required
                value={jobForm.number_of_openings}
                onChange={(e) => setJobForm({...jobForm, number_of_openings: parseInt(e.target.value)})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Salary Min</label>
              <input
                type="number"
                value={jobForm.salary_min}
                onChange={(e) => setJobForm({...jobForm, salary_min: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Salary Max</label>
              <input
                type="number"
                value={jobForm.salary_max}
                onChange={(e) => setJobForm({...jobForm, salary_max: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {loading ? 'Creating...' : 'Create Job'}
          </button>
        </form>
      </div>
    );
  }

  function AddCandidateForm() {
    return (
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">Add New Candidate</h3>
        <form onSubmit={handleAddCandidate} className="space-y-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-gray-700">Name</label>
              <input
                type="text"
                required
                value={candidateForm.name}
                onChange={(e) => setCandidateForm({...candidateForm, name: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Email</label>
              <input
                type="email"
                required
                value={candidateForm.email}
                onChange={(e) => setCandidateForm({...candidateForm, email: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Phone</label>
              <input
                type="tel"
                required
                value={candidateForm.phone}
                onChange={(e) => setCandidateForm({...candidateForm, phone: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Source</label>
              <select
                required
                value={candidateForm.source_id}
                onChange={(e) => setCandidateForm({...candidateForm, source_id: e.target.value})}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Select Source</option>
                {candidateSources.map(source => (
                  <option key={source.id} value={source.id}>{source.source_name}</option>
                ))}
              </select>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">Resume</label>
            <input
              type="file"
              accept=".pdf,.doc,.docx"
              onChange={(e) => setCandidateForm({...candidateForm, resume: e.target.files[0]})}
              className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {loading ? 'Adding...' : 'Add Candidate'}
          </button>
        </form>
             </div>
     );
   }

   // 3. Assign to Job Form
   function AssignToJobForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Assign Candidates to Job</h3>
         <form onSubmit={handleAssignToJob} className="space-y-4">
           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Select Job</label>
               <select
                 required
                 value={assignmentForm.job_id}
                 onChange={(e) => {
                   setAssignmentForm({...assignmentForm, job_id: e.target.value});
                   loadAvailableCandidates(e.target.value);
                 }}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Job</option>
                 {jobs.map(job => (
                   <option key={job.id} value={job.id}>{job.job_title} - {job.department}</option>
                 ))}
               </select>
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Assignment Status</label>
               <select
                 required
                 value={assignmentForm.assignment_status}
                 onChange={(e) => setAssignmentForm({...assignmentForm, assignment_status: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="Applied">Applied</option>
                 <option value="Shortlisted">Shortlisted</option>
               </select>
             </div>
           </div>

           {assignmentForm.assignment_status === 'Applied' && (
             <div>
               <label className="block text-sm font-medium text-gray-700">Email Content for Candidates</label>
               <textarea
                 required
                 rows={3}
                 value={assignmentForm.email_content}
                 onChange={(e) => setAssignmentForm({...assignmentForm, email_content: e.target.value})}
                 placeholder="Thank you for your interest in this position..."
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               />
             </div>
           )}

           <div>
             <label className="block text-sm font-medium text-gray-700 mb-2">Select Candidates</label>
             <div className="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3 space-y-2">
               {availableCandidates.map(candidate => (
                 <label key={candidate.id} className="flex items-center">
                   <input
                     type="checkbox"
                     checked={assignmentForm.candidate_ids.includes(candidate.id)}
                     onChange={(e) => {
                       if (e.target.checked) {
                         setAssignmentForm({
                           ...assignmentForm,
                           candidate_ids: [...assignmentForm.candidate_ids, candidate.id]
                         });
                       } else {
                         setAssignmentForm({
                           ...assignmentForm,
                           candidate_ids: assignmentForm.candidate_ids.filter(id => id !== candidate.id)
                         });
                       }
                     }}
                     className="mr-2"
                   />
                   <span>{candidate.name} - {candidate.email} ({candidate.current_status})</span>
                 </label>
               ))}
             </div>
           </div>

           <button
             type="submit"
             disabled={loading || assignmentForm.candidate_ids.length === 0}
             className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
           >
             {loading ? 'Assigning...' : 'Assign Candidates'}
           </button>
         </form>
       </div>
     );
   }

   // 4. Candidate Approval Form
   function ApproveCandidateForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Approve Applied Candidates</h3>
         <div className="space-y-4">
           {candidatesForApproval.length === 0 ? (
             <p className="text-gray-500">No candidates awaiting approval.</p>
           ) : (
             <>
               <div className="space-y-3">
                 {candidatesForApproval.map(assignment => (
                   <div key={assignment.id} className="flex items-center p-3 border border-gray-300 rounded-md">
                     <input
                       type="checkbox"
                       checked={assignment.selected || false}
                       onChange={(e) => {
                         setCandidatesForApproval(candidatesForApproval.map(item =>
                           item.id === assignment.id ? { ...item, selected: e.target.checked } : item
                         ));
                       }}
                       className="mr-3"
                     />
                     <div className="flex-1">
                       <div className="font-medium">{assignment.candidate.name}</div>
                       <div className="text-sm text-gray-600">
                         {assignment.candidate.email} - Applied for {assignment.job.job_title}
                       </div>
                       <div className="text-xs text-gray-500">
                         Applied on: {new Date(assignment.assigned_at).toLocaleDateString()}
                       </div>
                     </div>
                   </div>
                 ))}
               </div>
               <button
                 onClick={handleApproveCandidate}
                 disabled={loading || !candidatesForApproval.some(c => c.selected)}
                 className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
               >
                 {loading ? 'Approving...' : 'Approve Selected Candidates'}
               </button>
             </>
           )}
         </div>
       </div>
     );
   }

   // 5. Schedule Interview Form
   function ScheduleInterviewForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Schedule Interview</h3>
         <form onSubmit={handleScheduleInterview} className="space-y-4">
           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Select Candidate</label>
               <select
                 required
                 value={interviewForm.candidate_id}
                 onChange={(e) => setInterviewForm({...interviewForm, candidate_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Candidate</option>
                 {verifiedCandidates.map(assignment => (
                   <option key={assignment.id} value={assignment.candidate_id}>
                     {assignment.candidate.name} - {assignment.job.job_title}
                   </option>
                 ))}
               </select>
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Job</label>
               <select
                 required
                 value={interviewForm.job_id}
                 onChange={(e) => setInterviewForm({...interviewForm, job_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Job</option>
                 {jobs.map(job => (
                   <option key={job.id} value={job.id}>{job.job_title}</option>
                 ))}
               </select>
             </div>
           </div>

           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Interview Date & Time</label>
               <input
                 type="datetime-local"
                 required
                 value={interviewForm.interview_datetime}
                 onChange={(e) => setInterviewForm({...interviewForm, interview_datetime: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               />
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Interview Mode</label>
               <select
                 required
                 value={interviewForm.mode}
                 onChange={(e) => setInterviewForm({...interviewForm, mode: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="Video">Video</option>
                 <option value="In-person">In-person</option>
               </select>
             </div>
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">
               {interviewForm.mode === 'Video' ? 'Meeting Link' : 'Location'}
             </label>
             <input
               type={interviewForm.mode === 'Video' ? 'url' : 'text'}
               required
               value={interviewForm.meeting_link_or_location}
               onChange={(e) => setInterviewForm({...interviewForm, meeting_link_or_location: e.target.value})}
               placeholder={interviewForm.mode === 'Video' ? 'https://meet.google.com/...' : 'Conference Room A, 2nd Floor'}
               className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
             />
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">Interviewer Emails</label>
             {interviewForm.interviewer_emails.map((email, index) => (
               <div key={index} className="flex mt-1 mb-2">
                 <input
                   type="email"
                   required
                   value={email}
                   onChange={(e) => {
                     const newEmails = [...interviewForm.interviewer_emails];
                     newEmails[index] = e.target.value;
                     setInterviewForm({...interviewForm, interviewer_emails: newEmails});
                   }}
                   className="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="interviewer@company.com"
                 />
                 {interviewForm.interviewer_emails.length > 1 && (
                   <button
                     type="button"
                     onClick={() => {
                       const newEmails = interviewForm.interviewer_emails.filter((_, i) => i !== index);
                       setInterviewForm({...interviewForm, interviewer_emails: newEmails});
                     }}
                     className="ml-2 px-3 py-2 text-red-600 border border-red-300 rounded-md hover:bg-red-50"
                   >
                     Remove
                   </button>
                 )}
               </div>
             ))}
             <button
               type="button"
               onClick={() => setInterviewForm({
                 ...interviewForm,
                 interviewer_emails: [...interviewForm.interviewer_emails, '']
               })}
               className="text-sm text-indigo-600 hover:text-indigo-800"
             >
               + Add Another Interviewer
             </button>
           </div>

           <button
             type="submit"
             disabled={loading}
             className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
           >
             {loading ? 'Scheduling...' : 'Schedule Interview'}
           </button>
         </form>
       </div>
     );
   }

   // 6. Send Offer Form
   function SendOfferForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Send Job Offer</h3>
         <form onSubmit={handleSendOffer} className="space-y-4">
           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Select Candidate</label>
               <select
                 required
                 value={offerForm.candidate_id}
                 onChange={(e) => setOfferForm({...offerForm, candidate_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Candidate</option>
                 {verifiedCandidates.map(assignment => (
                   <option key={assignment.id} value={assignment.candidate_id}>
                     {assignment.candidate.name} - {assignment.job.job_title}
                   </option>
                 ))}
               </select>
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Job</label>
               <select
                 required
                 value={offerForm.job_id}
                 onChange={(e) => setOfferForm({...offerForm, job_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Job</option>
                 {jobs.map(job => (
                   <option key={job.id} value={job.id}>{job.job_title}</option>
                 ))}
               </select>
             </div>
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">Email Subject</label>
             <input
               type="text"
               required
               value={offerForm.subject_line}
               onChange={(e) => setOfferForm({...offerForm, subject_line: e.target.value})}
               placeholder="Job Offer - [Position Title]"
               className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
             />
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">Email Content</label>
             <textarea
               required
               rows={6}
               value={offerForm.email_content}
               onChange={(e) => setOfferForm({...offerForm, email_content: e.target.value})}
               placeholder="Dear [Candidate Name], We are pleased to extend an offer..."
               className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
             />
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">Offer Document</label>
             <input
               type="file"
               required
               accept=".pdf,.doc,.docx"
               onChange={(e) => setOfferForm({...offerForm, offer_document: e.target.files[0]})}
               className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
             />
           </div>

           <button
             type="submit"
             disabled={loading}
             className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
           >
             {loading ? 'Sending...' : 'Send Offer'}
           </button>
         </form>
       </div>
     );
   }

   // 7. Start Onboarding Form
   function StartOnboardingForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Start Employee Onboarding</h3>
         <form onSubmit={handleStartOnboarding} className="space-y-4">
           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Select Candidate</label>
               <select
                 required
                 value={onboardingForm.candidate_id}
                 onChange={(e) => setOnboardingForm({...onboardingForm, candidate_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Candidate</option>
                 {verifiedCandidates.map(assignment => (
                   <option key={assignment.id} value={assignment.candidate_id}>
                     {assignment.candidate.name} - {assignment.job.job_title}
                   </option>
                 ))}
               </select>
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Job</label>
               <select
                 required
                 value={onboardingForm.job_id}
                 onChange={(e) => setOnboardingForm({...onboardingForm, job_id: e.target.value})}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               >
                 <option value="">Select Job</option>
                 {jobs.map(job => (
                   <option key={job.id} value={job.id}>{job.job_title}</option>
                 ))}
               </select>
             </div>
           </div>

           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Start Date</label>
               <input
                 type="date"
                 required
                 value={onboardingForm.start_date}
                 onChange={(e) => setOnboardingForm({...onboardingForm, start_date: e.target.value})}
                 min={new Date().toISOString().split('T')[0]}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               />
             </div>
             <div>
               <label className="block text-sm font-medium text-gray-700">Manager Email</label>
               <input
                 type="email"
                 required
                 value={onboardingForm.manager_email}
                 onChange={(e) => setOnboardingForm({...onboardingForm, manager_email: e.target.value})}
                 placeholder="manager@finfinity.co.in"
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               />
             </div>
           </div>

           <div className="bg-blue-50 p-4 rounded-md">
             <h4 className="text-sm font-medium text-blue-900 mb-2">What happens when you start onboarding:</h4>
             <ul className="text-sm text-blue-700 space-y-1">
               <li>• Employee email will be auto-generated (firstname.lastname001@finfinity.co.in)</li>
               <li>• Employee record will be created in the system</li>
               <li>• Welcome email will be sent to the candidate</li>
               <li>• IT team will be notified for asset handover</li>
               <li>• Candidate status will be updated to "Hired"</li>
             </ul>
           </div>

           <button
             type="submit"
             disabled={loading}
             className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
           >
             {loading ? 'Starting Onboarding...' : 'Start Onboarding'}
           </button>
         </form>
       </div>
     );
   }

   // 8. Mark Resignation Form
   function MarkResignationForm() {
     return (
       <div>
         <h3 className="text-lg font-medium text-gray-900 mb-4">Mark Employee Resignation</h3>
         <form onSubmit={handleMarkResignation} className="space-y-4">
           <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
             <div>
               <label className="block text-sm font-medium text-gray-700">Last Working Day</label>
               <input
                 type="date"
                 required
                 value={resignationForm.last_working_day}
                 onChange={(e) => setResignationForm({...resignationForm, last_working_day: e.target.value})}
                 min={new Date().toISOString().split('T')[0]}
                 className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               />
             </div>
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700">Resignation Reason (Optional)</label>
             <textarea
               rows={3}
               value={resignationForm.resignation_reason}
               onChange={(e) => setResignationForm({...resignationForm, resignation_reason: e.target.value})}
               placeholder="Better opportunity, personal reasons, etc."
               className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
             />
           </div>

           <div>
             <label className="block text-sm font-medium text-gray-700 mb-2">Select Employees</label>
             <div className="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3 space-y-2">
               {activeEmployees.map(employee => (
                 <label key={employee.id} className="flex items-center">
                   <input
                     type="checkbox"
                     checked={resignationForm.employee_ids.includes(employee.id)}
                     onChange={(e) => {
                       if (e.target.checked) {
                         setResignationForm({
                           ...resignationForm,
                           employee_ids: [...resignationForm.employee_ids, employee.id]
                         });
                       } else {
                         setResignationForm({
                           ...resignationForm,
                           employee_ids: resignationForm.employee_ids.filter(id => id !== employee.id)
                         });
                       }
                     }}
                     className="mr-2"
                   />
                   <div>
                     <div className="font-medium">{employee.name}</div>
                     <div className="text-sm text-gray-600">{employee.employee_email} - {employee.job_title}</div>
                   </div>
                 </label>
               ))}
             </div>
           </div>

           <div className="bg-red-50 p-4 rounded-md">
             <h4 className="text-sm font-medium text-red-900 mb-2">What happens when you mark resignation:</h4>
             <ul className="text-sm text-red-700 space-y-1">
               <li>• Employee status will be updated to "Resigned"</li>
               <li>• IT team will be notified for asset recovery</li>
               <li>• Last working day will be recorded</li>
               <li>• Employee access may need to be revoked manually</li>
             </ul>
           </div>

           <button
             type="submit"
             disabled={loading || resignationForm.employee_ids.length === 0}
             className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
           >
             {loading ? 'Processing...' : 'Mark as Resigned'}
           </button>
         </form>
       </div>
     );
   }
 };

export default HRAdminTools; 
<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class EmailService
{
    private $client;
    private $accessToken;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30.0,
            'connect_timeout' => 10.0
        ]);
    }

    /**
     * Get application access token using client credentials flow
     */
    private function getApplicationAccessToken()
    {
        try {
            $tenantId = "fe2a29a6-1811-4b6b-9aa3-51773ff80ead";
            $clientId = "bd1ec95d-b6c6-4f3d-b697-2e1eae6e53a4";
            $clientSecret = "1XM8Q~UfQDegTkKmQflo31wHMg3fGHoN6XKbBbIt";

            Log::info('EmailService: Getting application access token for noreply emails');

            $response = $this->client->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $tokenData = json_decode($response->getBody(), true);
                $this->accessToken = $tokenData['access_token'];
                
                Log::info('EmailService: Application access token obtained successfully', [
                    'expires_in' => $tokenData['expires_in'] ?? 'unknown',
                    'token_type' => $tokenData['token_type'] ?? 'unknown'
                ]);
                
                return true;
            } else {
                Log::error('EmailService: Failed to get application access token', [
                    'status_code' => $response->getStatusCode()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('EmailService: Error getting application access token', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate token and refresh if needed
     */
    private function validateAndRefreshToken($tokenData)
    {
        try {
            // Check if token exists
            if (!isset($tokenData['access_token'])) {
                Log::error('EmailService: No access token in token data');
                return false;
            }

            // Check if token is expired (Microsoft tokens typically last 1 hour)
            if (isset($tokenData['expires_in']) && isset($tokenData['created_at'])) {
                $expirationTime = $tokenData['created_at'] + $tokenData['expires_in'];
                if (time() >= $expirationTime) {
                    Log::warning('EmailService: Access token expired, attempting refresh');
                    // Token is expired, try to refresh if we have a refresh token
                    if (isset($tokenData['refresh_token'])) {
                        return $this->refreshAccessToken($tokenData);
                    } else {
                        Log::error('EmailService: Token expired and no refresh token available');
                        return false;
                    }
                }
            }

            // Test the token by making a simple Graph API call
            $response = $this->client->get('https://graph.microsoft.com/v1.0/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenData['access_token'],
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                Log::info('EmailService: Token validation successful');
                return true;
            } else {
                Log::error('EmailService: Token validation failed', [
                    'status_code' => $response->getStatusCode()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('EmailService: Token validation error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Refresh access token using refresh token
     */
    private function refreshAccessToken($tokenData)
    {
        try {
            if (!isset($tokenData['refresh_token'])) {
                Log::error('EmailService: No refresh token available');
                return false;
            }

            Log::info('EmailService: Attempting to refresh access token');

            // Use Microsoft Graph token endpoint to refresh
            $response = $this->client->post('https://login.microsoftonline.com/' . env('MICROSOFT_TENANT_ID') . '/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => env('MICROSOFT_CLIENT_ID'),
                    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
                    'scope' => 'https://graph.microsoft.com/.default',
                    'refresh_token' => $tokenData['refresh_token'],
                    'grant_type' => 'refresh_token'
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $newTokenData = json_decode($response->getBody(), true);
                
                // Update session with new token data
                $updatedTokenData = array_merge($tokenData, $newTokenData);
                $updatedTokenData['created_at'] = time(); // Track when we got the new token
                
                Session::put('token', $updatedTokenData);
                
                Log::info('EmailService: Token refreshed successfully', [
                    'new_expires_in' => $newTokenData['expires_in'] ?? 'unknown'
                ]);
                
                return true;
            } else {
                Log::error('EmailService: Token refresh failed', [
                    'status_code' => $response->getStatusCode()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('EmailService: Token refresh failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send decline notification email to memo raiser
     */
    public function sendDeclineNotification($memo, $declineReason, $declinerName, $declinerEmail, $groupName)
    {
        try {
            // Get application access token for noreply email sending
            if (!$this->getApplicationAccessToken()) {
                Log::error('EmailService: Failed to get application access token');
                return false;
            }

            Log::info('EmailService: Using application access token for noreply email');

            // Prepare email content
            $subject = "Memo Declined: {$memo->description}";
            $htmlBody = $this->getDeclineEmailTemplate($memo, $declineReason, $declinerName, $groupName);
            
            // Prepare attachment
            $attachment = null;
            if ($memo->document_path && Storage::exists($memo->document_path)) {
                $attachment = $this->prepareAttachment($memo->document_path);
            }

            // Send email via Microsoft Graph using noreply address
            return $this->sendEmailFromNoreply(
                $memo->raised_by_email,
                $memo->raised_by_name,
                $subject,
                $htmlBody,
                $attachment
            );

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send decline notification', [
                'error' => $e->getMessage(),
                'memo_id' => $memo->id,
                'recipient' => $memo->raised_by_email
            ]);
            return false;
        }
    }

    /**
     * Create HTML email template for decline notification
     */
    private function getDeclineEmailTemplate($memo, $declineReason, $declinerName, $groupName)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
                .reason-box { background-color: #fff; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; }
                .footer { background-color: #6c757d; color: white; padding: 10px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; }
                .info-row { margin: 10px 0; }
                .label { font-weight: bold; color: #495057; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>❌ Memo Declined</h2>
                </div>
                
                <div class='content'>
                    <p>Dear {$memo->raised_by_name},</p>
                    
                    <p>Your memo submission has been <strong>declined</strong> by the {$groupName} team.</p>
                    
                    <div class='info-row'>
                        <span class='label'>Memo Description:</span> {$memo->description}
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Submitted Date:</span> {$memo->created_at->format('F j, Y \\a\\t g:i A')}
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Declined by:</span> {$declinerName} ({$groupName})
                    </div>
                    
                    <div class='reason-box'>
                        <strong>Decline Reason:</strong><br>
                        " . nl2br(htmlspecialchars($declineReason)) . "
                    </div>
                    
                    <p>Please review the feedback and feel free to resubmit your memo with the necessary adjustments.</p>
                    
                    " . ($memo->document_path ? "<p><strong>📎 Your original document is attached to this email.</strong></p>" : "") . "
                </div>
                
                <div class='footer'>
                    This is an automated notification from the Memo Approval System.<br>
                    Please do not reply to this email.
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Prepare file attachment for email
     */
    private function prepareAttachment($filePath)
    {
        try {
            $fileContent = Storage::get($filePath);
            $fileName = basename($filePath);
            $mimeType = Storage::mimeType($filePath);
            
            return [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $fileName,
                'contentType' => $mimeType,
                'contentBytes' => base64_encode($fileContent)
            ];

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to prepare attachment', [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);
            return null;
        }
    }

    /**
     * Send email from noreply address using application permissions
     */
    public function sendEmailFromNoreply($recipientEmail, $recipientName, $subject, $htmlBody, $attachment = null)
    {
        try {
            $senderEmail = "noreply@finfinity.co.in";
            $senderName = "FinFinity Portal";

            Log::info('EmailService: Preparing to send noreply email', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'sender' => $senderEmail,
                'has_attachment' => !empty($attachment),
                'token_length' => strlen($this->accessToken ?? ''),
                'has_token' => !empty($this->accessToken)
            ]);

            $emailData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlBody
                    ],
                    'from' => [
                        'emailAddress' => [
                            'address' => $senderEmail,
                            'name' => $senderName
                        ]
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $recipientEmail,
                                'name' => $recipientName
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => false // Don't save to sent items for noreply
            ];

            // Add attachment if provided
            if ($attachment) {
                $emailData['message']['attachments'] = [$attachment];
                Log::info('EmailService: Attachment added', [
                    'attachment_name' => $attachment['name'] ?? 'unknown',
                    'attachment_size' => isset($attachment['contentBytes']) ? strlen($attachment['contentBytes']) : 0
                ]);
            }

            Log::info('EmailService: Making Graph API call to send noreply email');

            // Send email from the specified user (noreply@finfinity.co.in)
            $response = $this->client->post("https://graph.microsoft.com/v1.0/users/{$senderEmail}/sendMail", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $emailData
            ]);

            Log::info('EmailService: Noreply email sent successfully', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'sender' => $senderEmail,
                'status_code' => $response->getStatusCode()
            ]);

            return true;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            
            Log::error('EmailService: Microsoft Graph API error for noreply email', [
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'sender' => 'noreply@finfinity.co.in',
                'access_token_length' => strlen($this->accessToken ?? ''),
                'has_token' => !empty($this->accessToken)
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send noreply email', [
                'error' => $e->getMessage(),
                'recipient' => $recipientEmail,
                'subject' => $subject
            ]);
            return false;
        }
    }

    /**
     * Generic method to send email with custom sender
     */
    public function sendEmail($recipientEmail, $recipientName, $subject, $htmlBody, $attachment = null, $fromEmail = null)
    {
        // If no custom sender specified, use noreply
        if (!$fromEmail) {
            return $this->sendEmailFromNoreply($recipientEmail, $recipientName, $subject, $htmlBody, $attachment);
        }

        // If custom sender specified, get app token and send from that address
        if (!$this->getApplicationAccessToken()) {
            Log::error('EmailService: Failed to get application access token for custom sender');
            return false;
        }

        return $this->sendEmailFromCustomSender($recipientEmail, $recipientName, $subject, $htmlBody, $attachment, $fromEmail);
    }

    /**
     * Send email from custom sender address
     */
    private function sendEmailFromCustomSender($recipientEmail, $recipientName, $subject, $htmlBody, $attachment, $fromEmail)
    {
        try {
            Log::info('EmailService: Preparing to send email from custom sender', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'sender' => $fromEmail,
                'has_attachment' => !empty($attachment)
            ]);

            $emailData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlBody
                    ],
                    'from' => [
                        'emailAddress' => [
                            'address' => $fromEmail,
                            'name' => 'FinFinity Portal'
                        ]
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $recipientEmail,
                                'name' => $recipientName
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => false
            ];

            // Add attachment if provided
            if ($attachment) {
                $emailData['message']['attachments'] = [$attachment];
            }

            // Send email from the specified user
            $response = $this->client->post("https://graph.microsoft.com/v1.0/users/{$fromEmail}/sendMail", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $emailData
            ]);

            Log::info('EmailService: Custom sender email sent successfully', [
                'recipient' => $recipientEmail,
                'sender' => $fromEmail,
                'status_code' => $response->getStatusCode()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send custom sender email', [
                'error' => $e->getMessage(),
                'recipient' => $recipientEmail,
                'sender' => $fromEmail
            ]);
            return false;
        }
    }

    /**
     * Original sendEmail method (kept for reference, but now redirects to new methods)
     */
    public function sendEmailOld($recipientEmail, $recipientName, $subject, $htmlBody, $attachment = null, $fromEmail = null)
    {
        try {
            Log::info('EmailService: Preparing to send email', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'has_attachment' => !empty($attachment),
                'token_length' => strlen($this->accessToken ?? ''),
                'has_token' => !empty($this->accessToken)
            ]);

            $emailData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlBody
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $recipientEmail,
                                'name' => $recipientName
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => true
            ];

            // Add attachment if provided
            if ($attachment) {
                $emailData['message']['attachments'] = [$attachment];
                Log::info('EmailService: Attachment added', [
                    'attachment_name' => $attachment['name'] ?? 'unknown',
                    'attachment_size' => isset($attachment['contentBytes']) ? strlen($attachment['contentBytes']) : 0
                ]);
            }

            Log::info('EmailService: Making Graph API call to send email');

            // Send email
            $response = $this->client->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $emailData
            ]);

            Log::info('EmailService: Decline notification sent successfully', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'status_code' => $response->getStatusCode()
            ]);

            return true;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            
            Log::error('EmailService: Microsoft Graph API error', [
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'access_token_length' => strlen($this->accessToken ?? ''),
                'has_token' => !empty($this->accessToken)
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send email via Graph API', [
                'error' => $e->getMessage(),
                'recipient' => $recipientEmail,
                'subject' => $subject
            ]);
            return false;
        }
    }
} 
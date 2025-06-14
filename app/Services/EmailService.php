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
     * Send decline notification email to memo raiser
     */
    public function sendDeclineNotification($memo, $declineReason, $declinerName, $declinerEmail, $groupName)
    {
        try {
            // Get access token from session
            $tokenData = Session::get('token');
            if (!$tokenData || !isset($tokenData['access_token'])) {
                Log::error('EmailService: No access token available for sending email');
                return false;
            }

            $this->accessToken = $tokenData['access_token'];

            // Prepare email content
            $subject = "Memo Declined: {$memo->description}";
            $htmlBody = $this->getDeclineEmailTemplate($memo, $declineReason, $declinerName, $groupName);
            
            // Prepare attachment
            $attachment = null;
            if ($memo->document_path && Storage::exists($memo->document_path)) {
                $attachment = $this->prepareAttachment($memo->document_path);
            }

            // Send email via Microsoft Graph
            return $this->sendEmail(
                $memo->raised_by_email,
                $memo->raised_by_name,
                $subject,
                $htmlBody,
                $attachment,
                $declinerEmail
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
     * Send email using Microsoft Graph API
     */
    public function sendEmail($recipientEmail, $recipientName, $subject, $htmlBody, $attachment = null, $fromEmail = null)
    {
        try {
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
            }

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
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SharedCalendarController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30.0,
            'connect_timeout' => 10.0
        ]);
    }

    /**
     * Get shared calendar events
     */
    public function index(Request $request)
    {
        try {
            // Check Microsoft Graph session authentication
            $user = Session::get('user');
            $tokenData = Session::get('token');
            
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json(['error' => 'No access token available'], 401);
            }

            $accessToken = $tokenData['access_token'];
            
            Log::info('SharedCalendarController: Fetching shared calendar events');

            // Get shared calendar events
            $events = $this->fetchSharedCalendarEvents($accessToken);

            return response()->json([
                'events' => $events,
                'count' => count($events),
                'message' => 'Shared calendar events retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('SharedCalendarController: Error fetching shared calendar', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Return fallback data in case of error
            return response()->json([
                'events' => $this->getFallbackEvents(),
                'count' => 0,
                'message' => 'Shared calendar unavailable - Microsoft Graph API error',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Fetch shared calendar events from Microsoft Graph
     */
    private function fetchSharedCalendarEvents($accessToken)
    {
        try {
            // Method 1: Try to get calendars that the user has access to
            $response = $this->client->get('https://graph.microsoft.com/v1.0/me/calendars', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $calendarsData = json_decode($response->getBody(), true);
            $calendars = $calendarsData['value'] ?? [];

            Log::info('SharedCalendarController: Found calendars', [
                'count' => count($calendars),
                'calendar_names' => array_column($calendars, 'name')
            ]);

            $allEvents = [];

            // Get events from each accessible calendar (excluding the primary one)
            foreach ($calendars as $calendar) {
                // Skip primary calendar (we already show this in main calendar)
                if ($calendar['isDefaultCalendar'] ?? false) {
                    continue;
                }

                try {
                    $calendarEvents = $this->getEventsFromCalendar($accessToken, $calendar['id']);
                    $allEvents = array_merge($allEvents, $calendarEvents);
                } catch (\Exception $e) {
                    Log::warning('SharedCalendarController: Failed to get events from calendar', [
                        'calendar_id' => $calendar['id'],
                        'calendar_name' => $calendar['name'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sort events by start time
            usort($allEvents, function($a, $b) {
                $aTime = $a['start']['dateTime'] ?? $a['start'];
                $bTime = $b['start']['dateTime'] ?? $b['start'];
                return strtotime($aTime) - strtotime($bTime);
            });

            return array_slice($allEvents, 0, 10); // Return max 10 events

        } catch (RequestException $e) {
            Log::error('SharedCalendarController: Microsoft Graph API error', [
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown'
            ]);
            
            // If shared calendar access fails, try alternative approach
            return $this->tryAlternativeSharedCalendarAccess($accessToken);
        }
    }

    /**
     * Get events from a specific calendar
     */
    private function getEventsFromCalendar($accessToken, $calendarId)
    {
        $startTime = now()->toISOString();
        $endTime = now()->addDays(7)->toISOString();

        $response = $this->client->get("https://graph.microsoft.com/v1.0/me/calendars/{$calendarId}/events", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ],
            'query' => [
                '$select' => 'id,subject,start,end,location,organizer,attendees,isAllDay',
                '$filter' => "start/dateTime ge '{$startTime}' and start/dateTime le '{$endTime}'",
                '$orderby' => 'start/dateTime',
                '$top' => 50
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['value'] ?? [];
    }

    /**
     * Try alternative approach for shared calendar access
     */
    private function tryAlternativeSharedCalendarAccess($accessToken)
    {
        try {
            // Alternative: Get events that the user is invited to (as attendee)
            $response = $this->client->get('https://graph.microsoft.com/v1.0/me/events', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    '$select' => 'id,subject,start,end,location,organizer,attendees,isAllDay',
                    '$filter' => "start/dateTime ge '" . now()->toISOString() . "' and start/dateTime le '" . now()->addDays(7)->toISOString() . "'",
                    '$orderby' => 'start/dateTime',
                    '$top' => 10
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $events = $data['value'] ?? [];

            // Filter for events where user is not the organizer (indicating shared/invited events)
            $userEmail = Session::get('user')['profile']['userPrincipalName'] ?? Session::get('user')['profile']['mail'] ?? '';
            
            $sharedEvents = array_filter($events, function($event) use ($userEmail) {
                $organizerEmail = $event['organizer']['emailAddress']['address'] ?? '';
                return strtolower($organizerEmail) !== strtolower($userEmail);
            });

            return array_values($sharedEvents);

        } catch (\Exception $e) {
            Log::error('SharedCalendarController: Alternative approach failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Fallback events in case of API failures
     */
    private function getFallbackEvents()
    {
        // Return empty array instead of dummy data
        // Frontend will handle showing "no shared events available" message
        return [];
    }
} 
<?php
defined('ANIDAO') or die('Direct access not permitted');

/**
 * Class to handle admin functionality via Telegram
 */
class AdminHandler {
    private $bot;
    private $db;
    private $adminStates = [];
    private $adminData = [];
    
    public function __construct($bot, $db) {
        $this->bot = $bot;
        $this->db = $db;
    }
    
    /**
     * Show admin panel
     */
    public function showAdminPanel($chatId) {
        $keyboard = [
            [['text' => '➕ Add New Anime']],
            [['text' => '📺 Add New Episode']],
            [['text' => '📋 List Anime']]
        ];
        
        $this->bot->sendMessageWithKeyboard(
            $chatId,
            "👑 <b>Admin Panel</b>\n\nSelect an action below:",
            $keyboard,
            true,
            true
        );
        
        // Reset admin state
        $this->setAdminState($chatId, 'main_menu');
    }
    
    /**
     * Handle admin text messages
     */
    public function handleAdminMessage($chatId, $text, $message) {
        $state = $this->getAdminState($chatId);
        
        // Main menu options
        if ($state === 'main_menu') {
            if ($text === '➕ Add New Anime') {
                $this->startAddAnime($chatId);
                return true;
            } else if ($text === '📺 Add New Episode') {
                $this->startAddEpisode($chatId);
                return true;
            } else if ($text === '📋 List Anime') {
                $this->listAnime($chatId);
                return true;
            }
        }
        
        // Add anime flow
        else if (strpos($state, 'add_anime_') === 0) {
            return $this->handleAddAnimeState($chatId, $state, $text);
        }
        
        // Add episode flow
        else if (strpos($state, 'add_episode_') === 0) {
            return $this->handleAddEpisodeState($chatId, $state, $text);
        }
        
        return false;
    }
    
    /**
     * Handle admin callback queries
     */
    public function handleAdminCallback($callbackQuery) {
        $data = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        
        // Handle anime list pagination
        if (strpos($data, 'admin_list_anime_page_') === 0) {
            $page = (int)str_replace('admin_list_anime_page_', '', $data);
            $this->listAnime($chatId, $page, $messageId);
            return;
        }
        
        // Handle anime selection for adding episode
        if (strpos($data, 'admin_select_anime_') === 0) {
            $animeId = (int)str_replace('admin_select_anime_', '', $data);
            $this->selectAnimeForEpisode($chatId, $animeId, $messageId);
            return;
        }
        
        // Handle genre selection
        if (strpos($data, 'admin_select_genre_') === 0) {
            $genreId = (int)str_replace('admin_select_genre_', '', $data);
            $this->selectGenre($chatId, $genreId, $messageId);
            return;
        }
        
        // Handle done with genre selection
        if ($data === 'admin_genres_done') {
            $this->finishGenreSelection($chatId, $messageId);
            return;
        }
    }
    
    /**
     * Start the add anime flow
     */
    private function startAddAnime($chatId) {
        $this->bot->sendMessage($chatId, "📝 <b>Add New Anime</b>\n\nPlease enter the anime title:");
        $this->setAdminState($chatId, 'add_anime_title');
        $this->setAdminData($chatId, 'anime', []);
    }
    
    /**
     * Handle add anime state machine
     */
    private function handleAddAnimeState($chatId, $state, $text) {
        switch ($state) {
            case 'add_anime_title':
                $this->setAdminDataValue($chatId, 'anime', 'title', $text);
                $this->bot->sendMessage($chatId, "📝 Now enter the description of the anime:");
                $this->setAdminState($chatId, 'add_anime_description');
                return true;
                
            case 'add_anime_description':
                $this->setAdminDataValue($chatId, 'anime', 'description', $text);
                $this->bot->sendMessage($chatId, "📝 Enter the year of release (e.g., 2023):");
                $this->setAdminState($chatId, 'add_anime_year');
                return true;
                
            case 'add_anime_year':
                if (!is_numeric($text) || strlen($text) !== 4) {
                    $this->bot->sendMessage($chatId, "❌ Please enter a valid 4-digit year (e.g., 2023):");
                    return true;
                }
                
                $this->setAdminDataValue($chatId, 'anime', 'year', (int)$text);
                $this->bot->sendMessage($chatId, "📝 Enter the cover image URL (leave empty if none):");
                $this->setAdminState($chatId, 'add_anime_cover');
                return true;
                
            case 'add_anime_cover':
                $this->setAdminDataValue($chatId, 'anime', 'cover_image', $text ?: null);
                
                // Send status options
                $keyboard = [
                    [['text' => 'Ongoing']],
                    [['text' => 'Completed']],
                    [['text' => 'Upcoming']]
                ];
                
                $this->bot->sendMessageWithKeyboard(
                    $chatId,
                    "📝 Select the status of the anime:",
                    $keyboard,
                    true,
                    true
                );
                
                $this->setAdminState($chatId, 'add_anime_status');
                return true;
                
            case 'add_anime_status':
                $status = strtolower($text);
                if (!in_array($status, ['ongoing', 'completed', 'upcoming'])) {
                    $status = 'ongoing'; // Default
                }
                
                $this->setAdminDataValue($chatId, 'anime', 'status', $status);
                
                // Show genres for selection
                $this->showGenreSelection($chatId);
                return true;
                
            case 'add_anime_genres':
                // This is handled by callbacks, but we need to check for "done" message
                if (strtolower($text) === 'done') {
                    $this->finishGenreSelection($chatId);
                }
                return true;
                
            case 'add_anime_confirm':
                if (strtolower($text) === 'yes') {
                    $this->saveNewAnime($chatId);
                } else {
                    $this->bot->sendMessage($chatId, "❌ Anime creation cancelled.");
                    $this->showAdminPanel($chatId);
                }
                return true;
        }
        
        return false;
    }
    
    /**
     * Show genre selection for anime
     */
    private function showGenreSelection($chatId) {
        // Get all genres
        $genres = $this->db->fetchAll("SELECT * FROM genres ORDER BY name");
        
        // Create inline keyboard with genres
        $keyboard = [];
        $row = [];
        
        foreach ($genres as $i => $genre) {
            $row[] = [
                'text' => $genre['name'],
                'callback_data' => 'admin_select_genre_' . $genre['id']
            ];
            
            // 2 genres per row
            if (count($row) === 2 || $i === count($genres) - 1) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        
        // Add done button
        $keyboard[] = [[
            'text' => '✅ Done Selecting Genres',
            'callback_data' => 'admin_genres_done'
        ]];
        
        // Initialize selected genres array
        $this->setAdminDataValue($chatId, 'anime', 'genres', []);
        
        $this->bot->sendMessageWithInlineKeyboard(
            $chatId,
            "📝 Select genres for this anime (click multiple):\n\nSelected: None",
            $keyboard
        );
        
        $this->setAdminState($chatId, 'add_anime_genres');
    }
    
    /**
     * Handle genre selection
     */
    private function selectGenre($chatId, $genreId, $messageId) {
        // Get current genres
        $animeData = $this->getAdminData($chatId, 'anime');
        $selectedGenres = $animeData['genres'] ?? [];
        
        // Toggle selected state
        if (in_array($genreId, $selectedGenres)) {
            $selectedGenres = array_diff($selectedGenres, [$genreId]);
        } else {
            $selectedGenres[] = $genreId;
        }
        
        // Update admin data
        $this->setAdminDataValue($chatId, 'anime', 'genres', $selectedGenres);
        
        // Get genre names for display
        $genreNames = [];
        if (!empty($selectedGenres)) {
            $placeholders = implode(',', array_fill(0, count($selectedGenres), '?'));
            $genreNames = $this->db->fetchAll(
                "SELECT name FROM genres WHERE id IN ($placeholders)",
                $selectedGenres
            );
            $genreNames = array_column($genreNames, 'name');
        }
        
        // Update message
        $selectedText = empty($genreNames) ? 'None' : implode(', ', $genreNames);
        
        // Recreate keyboard
        $genres = $this->db->fetchAll("SELECT * FROM genres ORDER BY name");
        $keyboard = [];
        $row = [];
        
        foreach ($genres as $i => $genre) {
            $isSelected = in_array($genre['id'], $selectedGenres);
            $text = ($isSelected ? '✅ ' : '') . $genre['name'];
            
            $row[] = [
                'text' => $text,
                'callback_data' => 'admin_select_genre_' . $genre['id']
            ];
            
            // 2 genres per row
            if (count($row) === 2 || $i === count($genres) - 1) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        
        // Add done button
        $keyboard[] = [[
            'text' => '✅ Done Selecting Genres',
            'callback_data' => 'admin_genres_done'
        ]];
        
        $this->bot->editMessageText(
            $chatId,
            $messageId,
            "📝 Select genres for this anime (click multiple):\n\nSelected: $selectedText",
            ['inline_keyboard' => $keyboard]
        );
    }
    
    /**
     * Finish genre selection
     */
    private function finishGenreSelection($chatId, $messageId = null) {
        $animeData = $this->getAdminData($chatId, 'anime');
        
        // Show confirmation message
        $message = "📝 <b>Confirm Anime Details:</b>\n\n";
        $message .= "Title: " . $animeData['title'] . "\n";
        $message .= "Year: " . $animeData['year'] . "\n";
        $message .= "Status: " . ucfirst($animeData['status']) . "\n";
        
        // Get genre names
        $genreNames = [];
        if (!empty($animeData['genres'])) {
            $placeholders = implode(',', array_fill(0, count($animeData['genres']), '?'));
            $genreNames = $this->db->fetchAll(
                "SELECT name FROM genres WHERE id IN ($placeholders)",
                $animeData['genres']
            );
            $genreNames = array_column($genreNames, 'name');
        }
        
        $message .= "Genres: " . (empty($genreNames) ? 'None' : implode(', ', $genreNames)) . "\n";
        $message .= "Cover Image: " . ($animeData['cover_image'] ? "Yes" : "No") . "\n\n";
        $message .= "Do you want to save this anime? (Yes/No)";
        
        // If this is a callback, edit the message
        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $message);
        } else {
            $this->bot->sendMessage($chatId, $message);
        }
        
        $this->setAdminState($chatId, 'add_anime_confirm');
    }
    
    /**
     * Save new anime to database
     */
    private function saveNewAnime($chatId) {
        $animeData = $this->getAdminData($chatId, 'anime');
        
        try {
            // Start transaction
            $conn = $this->db->getConnection();
            $conn->beginTransaction();
            
            // Insert anime
            $sql = "INSERT INTO anime (title, description, year, cover_image, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $animeData['title'],
                $animeData['description'],
                $animeData['year'],
                $animeData['cover_image'],
                $animeData['status']
            ]);
            
            $animeId = $this->db->lastInsertId();
            
            // Insert genres
            if (!empty($animeData['genres'])) {
                foreach ($animeData['genres'] as $genreId) {
                    $this->db->query(
                        "INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?)",
                        [$animeId, $genreId]
                    );
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $this->bot->sendMessage($chatId, "✅ Anime successfully added!\n\nID: $animeId\nTitle: {$animeData['title']}");
            
            // Ask if admin wants to add an episode now
            $keyboard = [
                [['text' => 'Yes, add episode now']],
                [['text' => 'No, return to menu']]
            ];
            
            $this->bot->sendMessageWithKeyboard(
                $chatId,
                "Do you want to add an episode for this anime now?",
                $keyboard,
                true,
                true
            );
            
            // Set state with anime ID
            $this->setAdminState($chatId, 'add_episode_choice');
            $this->setAdminData($chatId, 'episode', ['anime_id' => $animeId]);
            
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $this->bot->sendMessage($chatId, "❌ Error adding anime: " . $e->getMessage());
            $this->showAdminPanel($chatId);
        }
    }
    
    /**
     * Start the add episode flow
     */
    private function startAddEpisode($chatId) {
        // Get all anime for selection
        $this->listAnime($chatId, 1, null, true);
        $this->setAdminState($chatId, 'add_episode_select_anime');
        $this->setAdminData($chatId, 'episode', []);
    }
    
    /**
     * List anime (paginated)
     */
    private function listAnime($chatId, $page = 1, $messageId = null, $forSelection = false) {
        $perPage = 5;
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $totalCount = $this->db->fetch("SELECT COUNT(*) as count FROM anime")['count'];
        $totalPages = ceil($totalCount / $perPage);
        
        // Get anime list
        $anime = $this->db->fetchAll(
            "SELECT id, title, year, status FROM anime ORDER BY title LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );
        
        if (empty($anime)) {
            $this->bot->sendMessage($chatId, "❌ No anime found. Please add some first.");
            $this->showAdminPanel($chatId);
            return;
        }
        
        // Build message
        $message = $forSelection 
            ? "📝 <b>Select an anime to add episode:</b>\n\n"
            : "📋 <b>Anime List</b> (Page $page of $totalPages):\n\n";
        
        foreach ($anime as $a) {
            $message .= "ID: {$a['id']} - <b>{$a['title']}</b> ({$a['year']}) - " . ucfirst($a['status']) . "\n";
        }
        
        // Build keyboard
        $keyboard = [];
        
        // For episode selection, add buttons for each anime
        if ($forSelection) {
            foreach ($anime as $a) {
                $keyboard[] = [[
                    'text' => $a['id'] . ": " . $a['title'],
                    'callback_data' => 'admin_select_anime_' . $a['id']
                ]];
            }
        }
        
        // Pagination buttons
        $paginationRow = [];
        
        if ($page > 1) {
            $paginationRow[] = [
                'text' => '◀️ Previous',
                'callback_data' => 'admin_list_anime_page_' . ($page - 1)
            ];
        }
        
        if ($page < $totalPages) {
            $paginationRow[] = [
                'text' => 'Next ▶️',
                'callback_data' => 'admin_list_anime_page_' . ($page + 1)
            ];
        }
        
        if (!empty($paginationRow)) {
            $keyboard[] = $paginationRow;
        }
        
        if (!$forSelection) {
            $keyboard[] = [[
                'text' => 'Back to Admin Panel',
                'callback_data' => 'admin_back_to_panel'
            ]];
        }
        
        $replyMarkup = ['inline_keyboard' => $keyboard];
        
        // Send or edit message
        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $message, $replyMarkup);
        } else {
            $this->bot->sendMessage($chatId, $message, $replyMarkup);
        }
    }
    
    /**
     * Select anime for adding episode
     */
    private function selectAnimeForEpisode($chatId, $animeId, $messageId) {
        // Get anime details
        $anime = $this->db->fetch(
            "SELECT title FROM anime WHERE id = ?",
            [$animeId]
        );
        
        if (!$anime) {
            $this->bot->editMessageText($chatId, $messageId, "❌ Anime not found.");
            return;
        }
        
        // Save selected anime ID
        $this->setAdminData($chatId, 'episode', ['anime_id' => $animeId]);
        
        // Get the latest episode number
        $latestEpisode = $this->db->fetch(
            "SELECT MAX(episode_number) as max_episode FROM episodes WHERE anime_id = ?",
            [$animeId]
        );
        
        $nextEpisode = 1;
        if ($latestEpisode && $latestEpisode['max_episode']) {
            $nextEpisode = $latestEpisode['max_episode'] + 1;
        }
        
        // Edit message
        $this->bot->editMessageText(
            $chatId, 
            $messageId, 
            "📝 Selected anime: <b>{$anime['title']}</b>\n\nPlease enter the episode number (suggested: $nextEpisode):"
        );
        
        $this->setAdminState($chatId, 'add_episode_number');
    }
    
    /**
     * Handle add episode state machine
     */
    private function handleAddEpisodeState($chatId, $state, $text) {
        switch ($state) {
            case 'add_episode_choice':
                if ($text === 'Yes, add episode now') {
                    $animeId = $this->getAdminData($chatId, 'episode')['anime_id'];
                    $anime = $this->db->fetch("SELECT title FROM anime WHERE id = ?", [$animeId]);
                    
                    // Get the latest episode number
                    $latestEpisode = $this->db->fetch(
                        "SELECT MAX(episode_number) as max_episode FROM episodes WHERE anime_id = ?",
                        [$animeId]
                    );
                    
                    $nextEpisode = 1;
                    if ($latestEpisode && $latestEpisode['max_episode']) {
                        $nextEpisode = $latestEpisode['max_episode'] + 1;
                    }
                    
                    $this->bot->sendMessage(
                        $chatId, 
                        "📝 Adding episode for: <b>{$anime['title']}</b>\n\nPlease enter the episode number (suggested: $nextEpisode):"
                    );
                    
                    $this->setAdminState($chatId, 'add_episode_number');
                } else {
                    $this->showAdminPanel($chatId);
                }
                return true;
                
            case 'add_episode_number':
                if (!is_numeric($text) || (int)$text <= 0) {
                    $this->bot->sendMessage($chatId, "❌ Please enter a valid positive episode number:");
                    return true;
                }
                
                $this->setAdminDataValue($chatId, 'episode', 'episode_number', (int)$text);
                $this->bot->sendMessage($chatId, "📝 Enter the episode title (optional, leave empty if none):");
                $this->setAdminState($chatId, 'add_episode_title');
                return true;
                
            case 'add_episode_title':
                $this->setAdminDataValue($chatId, 'episode', 'title', $text ?: null);
                $this->bot->sendMessage($chatId, "📝 Enter the Bunny.net stream ID (e.g., 412175/fa9e4829-e5d9-47d5-a4d9-77e945fa08d5):");
                $this->setAdminState($chatId, 'add_episode_stream_id');
                return true;
                
            case 'add_episode_stream_id':
                // Validate the stream ID format
                if (!preg_match('/^\d+\/[a-zA-Z0-9\-]+$/', $text)) {
                    $this->bot->sendMessage($chatId, "❌ Invalid Bunny.net stream ID format. Please enter in the format: 412175/fa9e4829-e5d9-47d5-a4d9-77e945fa08d5");
                    return true;
                }
                
                $this->setAdminDataValue($chatId, 'episode', 'bunny_stream_id', $text);
                
                // Show confirmation
                $episodeData = $this->getAdminData($chatId, 'episode');
                $anime = $this->db->fetch("SELECT title FROM anime WHERE id = ?", [$episodeData['anime_id']]);
                
                $message = "📝 <b>Confirm Episode Details:</b>\n\n";
                $message .= "Anime: " . $anime['title'] . "\n";
                $message .= "Episode Number: " . $episodeData['episode_number'] . "\n";
                $message .= "Title: " . ($episodeData['title'] ?: "None") . "\n";
                $message .= "Bunny Stream ID: " . $episodeData['bunny_stream_id'] . "\n\n";
                $message .= "Do you want to save this episode? (Yes/No)";
                
                $this->bot->sendMessage($chatId, $message);
                $this->setAdminState($chatId, 'add_episode_confirm');
                return true;
                
            case 'add_episode_confirm':
                if (strtolower($text) === 'yes') {
                    $this->saveNewEpisode($chatId);
                } else {
                    $this->bot->sendMessage($chatId, "❌ Episode creation cancelled.");
                    $this->showAdminPanel($chatId);
                }
                return true;
        }
        
        return false;
    }
    
    /**
     * Save new episode to database
     */
    private function saveNewEpisode($chatId) {
        $episodeData = $this->getAdminData($chatId, 'episode');
        
        try {
            // Check if episode already exists
            $existingEpisode = $this->db->fetch(
                "SELECT id FROM episodes WHERE anime_id = ? AND episode_number = ?",
                [$episodeData['anime_id'], $episodeData['episode_number']]
            );
            
            if ($existingEpisode) {
                $this->bot->sendMessage($chatId, "❌ Episode {$episodeData['episode_number']} already exists for this anime.");
                $this->showAdminPanel($chatId);
                return;
            }
            
            // Insert episode
            $sql = "INSERT INTO episodes (anime_id, episode_number, title, bunny_stream_id) 
                    VALUES (?, ?, ?, ?)";
            $this->db->query($sql, [
                $episodeData['anime_id'],
                $episodeData['episode_number'],
                $episodeData['title'],
                $episodeData['bunny_stream_id']
            ]);
            
            $episodeId = $this->db->lastInsertId();
            
            // Get anime title
            $anime = $this->db->fetch("SELECT title FROM anime WHERE id = ?", [$episodeData['anime_id']]);
            
            $this->bot->sendMessage(
                $chatId, 
                "✅ Episode successfully added!\n\nAnime: {$anime['title']}\nEpisode: {$episodeData['episode_number']}" .
                ($episodeData['title'] ? "\nTitle: {$episodeData['title']}" : "")
            );
            
            // Ask if admin wants to add another episode
            $keyboard = [
                [['text' => 'Add another episode']],
                [['text' => 'Return to menu']]
            ];
            
            $this->bot->sendMessageWithKeyboard(
                $chatId,
                "What would you like to do next?",
                $keyboard,
                true,
                true
            );
            
            // Keep anime_id for next episode
            $this->setAdminData($chatId, 'episode', ['anime_id' => $episodeData['anime_id']]);
            $this->setAdminState($chatId, 'add_episode_next');
            
        } catch (Exception $e) {
            $this->bot->sendMessage($chatId, "❌ Error adding episode: " . $e->getMessage());
            $this->showAdminPanel($chatId);
        }
    }
    
    /**
     * Get admin state
     */
    private function getAdminState($chatId) {
        return $this->adminStates[$chatId] ?? 'main_menu';
    }
    
    /**
     * Set admin state
     */
    private function setAdminState($chatId, $state) {
        $this->adminStates[$chatId] = $state;
    }
    
    /**
     * Get admin data
     */
    private function getAdminData($chatId, $key = null) {
        if ($key === null) {
            return $this->adminData[$chatId] ?? [];
        }
        
        return $this->adminData[$chatId][$key] ?? [];
    }
    
    /**
     * Set admin data
     */
    private function setAdminData($chatId, $key, $data) {
        if (!isset($this->adminData[$chatId])) {
            $this->adminData[$chatId] = [];
        }
        
        $this->adminData[$chatId][$key] = $data;
    }
    
    /**
     * Set admin data value
     */
    private function setAdminDataValue($chatId, $key, $field, $value) {
        if (!isset($this->adminData[$chatId])) {
            $this->adminData[$chatId] = [];
        }
        
        if (!isset($this->adminData[$chatId][$key])) {
            $this->adminData[$chatId][$key] = [];
        }
        
        $this->adminData[$chatId][$key][$field] = $value;
    }
}

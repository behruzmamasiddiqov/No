<?php
// Telegram Bot API wrapper class
class TelegramBot {
    private $token;
    private $apiUrl;
    
    public function __construct($token) {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }
    
    /**
     * Send a request to the Telegram API
     */
    public function request($method, $params = []) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Send a text message to a user
     */
    public function sendMessage($chatId, $text, $replyMarkup = null) {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        
        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->request('sendMessage', $params);
    }
    
    /**
     * Send a text message with a keyboard
     */
    public function sendMessageWithKeyboard($chatId, $text, $keyboard, $oneTime = false, $resize = true) {
        $replyMarkup = [
            'keyboard' => $keyboard,
            'one_time_keyboard' => $oneTime,
            'resize_keyboard' => $resize
        ];
        
        return $this->sendMessage($chatId, $text, $replyMarkup);
    }
    
    /**
     * Send a text message with inline keyboard
     */
    public function sendMessageWithInlineKeyboard($chatId, $text, $keyboard) {
        $replyMarkup = [
            'inline_keyboard' => $keyboard
        ];
        
        return $this->sendMessage($chatId, $text, $replyMarkup);
    }
    
    /**
     * Set webhook for receiving updates
     */
    public function setWebhook($url) {
        return $this->request('setWebhook', [
            'url' => $url
        ]);
    }
    
    /**
     * Remove webhook
     */
    public function deleteWebhook() {
        return $this->request('deleteWebhook');
    }
    
    /**
     * Get webhook info
     */
    public function getWebhookInfo() {
        return $this->request('getWebhookInfo');
    }
    
    /**
     * Answer callback query
     */
    public function answerCallbackQuery($callbackQueryId, $text = '', $showAlert = false) {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert
        ]);
    }
    
    /**
     * Edit message text
     */
    public function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->request('editMessageText', $params);
    }
}

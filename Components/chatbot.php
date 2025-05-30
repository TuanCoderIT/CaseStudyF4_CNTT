<?php
// Khởi tạo phiên làm việc nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối đến CSDL nếu chưa có
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db.php';
}
?>

<div class="chatbot-container">
    <div class="chatbot-toggle" id="chatToggle">
        <i class="fas fa-comments"></i>
    </div>
    <div class="chatbot-box" id="chatBox">
        <div class="chatbot-header">
            <h5><i class="fas fa-robot me-2"></i> Trợ lý ảo PhòngTrọ</h5>
            <div class="chatbot-controls">
                <button class="minimize-btn" id="minimizeChat"><i class="fas fa-minus"></i></button>
                <button class="close-btn" id="closeChat"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="chatbot-messages" id="chatMessages">
            <div class="message bot">
                <div class="message-content">
                    <p>Xin chào! Tôi là trợ lý ảo của PhòngTrọ. Tôi có thể giúp bạn tìm phòng, xem các phòng trọ phổ biến hoặc trả lời các câu hỏi về dịch vụ của chúng tôi.</p>
                    <p>Bạn có thể hỏi tôi những câu như:</p>
                    <ul class="suggestion-list">
                        <li class="suggestion-item">"Top 3 phòng trọ được xem nhiều nhất"</li>
                        <li class="suggestion-item">"Phòng trọ có giá dưới 2 triệu"</li>
                        <li class="suggestion-item">"Phòng trọ gần Đại học Vinh"</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatInput" placeholder="Nhập câu hỏi của bạn...">
            <button id="sendMessage"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    .chatbot-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .chatbot-toggle {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6e8efb, #4e73df);
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 6px 16px rgba(78, 115, 223, 0.3);
        font-size: 26px;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .chatbot-toggle:hover {
        transform: scale(1.08) rotate(5deg);
        box-shadow: 0 8px 25px rgba(78, 115, 223, 0.4);
    }

    .chatbot-toggle i {
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    }

    .chatbot-box {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 380px;
        height: 520px;
        background-color: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), 0 1px 5px rgba(0, 0, 0, 0.1);
        display: none;
        flex-direction: column;
        overflow: hidden;
        transition: all 0.3s ease;
        animation: slideUp 0.4s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        border: 1px solid rgba(78, 115, 223, 0.2);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .chatbot-header {
        background: linear-gradient(135deg, #4e73df, #3b5bdb);
        color: white;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .chatbot-header h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
    }

    .chatbot-header h5 i {
        margin-right: 8px;
        font-size: 20px;
    }

    .chatbot-controls button {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        margin-left: 10px;
        cursor: pointer;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 12px;
    }

    .chatbot-controls button:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .chatbot-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background-color: #f8f9fc;
        background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23e2e6f3' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E");
        scroll-behavior: smooth;
    }

    .chatbot-messages::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .chatbot-messages::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.03);
        border-radius: 10px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb {
        background: rgba(78, 115, 223, 0.2);
        border-radius: 10px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb:hover {
        background: rgba(78, 115, 223, 0.4);
    }

    .message {
        margin-bottom: 18px;
        display: flex;
        flex-direction: column;
    }

    .message.user {
        align-items: flex-end;
    }

    .message.bot {
        align-items: flex-start;
    }

    .message-content {
        max-width: 85%;
        padding: 12px 18px;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        animation: fadeInScale 0.3s ease;
        line-height: 1.5;
        position: relative;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .message.user .message-content {
        background: linear-gradient(135deg, #4e73df, #3b5bdb);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 3px 10px rgba(59, 91, 219, 0.2);
    }

    .message.user .message-content::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: -8px;
        width: 16px;
        height: 16px;
        background: linear-gradient(225deg, #4e73df, #3b5bdb);
        clip-path: polygon(0 0, 0% 100%, 100% 100%);
    }

    .message.bot .message-content {
        background: white;
        color: #333;
        border-bottom-left-radius: 4px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }

    .message.bot .message-content::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: -8px;
        width: 16px;
        height: 16px;
        background: white;
        clip-path: polygon(0 100%, 100% 100%, 100% 0);
        border-left: 1px solid rgba(0, 0, 0, 0.04);
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }

    .message-content p {
        margin: 0 0 10px 0;
        font-size: 14px;
    }

    .message-content p:last-child {
        margin-bottom: 0;
    }

    .message-content ul {
        margin: 8px 0;
        padding-left: 20px;
    }

    .message-content li {
        margin-bottom: 6px;
        font-size: 13.5px;
    }

    .suggestion-list {
        list-style: none;
        padding-left: 0;
        margin-top: 12px;
    }

    .suggestion-item {
        display: inline-block;
        padding: 8px 14px;
        margin: 0 5px 8px 0;
        background: linear-gradient(135deg, #f8f9fc, #edf0f9);
        border: 1px solid rgba(78, 115, 223, 0.15);
        border-radius: 20px;
        font-size: 13px;
        color: #4e73df;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
    }

    .suggestion-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .suggestion-item:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #f1f3ff, #e8ecf9);
        box-shadow: 0 4px 8px rgba(78, 115, 223, 0.15);
        border-color: rgba(78, 115, 223, 0.3);
    }

    .suggestion-item:hover::before {
        opacity: 1;
    }

    .suggestion-item:active {
        transform: translateY(1px);
        box-shadow: 0 1px 3px rgba(78, 115, 223, 0.1);
    }

    .chatbot-input {
        display: flex;
        padding: 15px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        background-color: white;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
        position: relative;
    }

    .chatbot-input input {
        flex: 1;
        padding: 12px 18px;
        border: 1px solid rgba(78, 115, 223, 0.2);
        border-radius: 25px;
        outline: none;
        font-size: 14px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
    }

    .chatbot-input input:focus {
        border-color: #4e73df;
        box-shadow: 0 3px 10px rgba(78, 115, 223, 0.15);
    }

    .chatbot-input input::placeholder {
        color: #aab0bc;
    }

    .chatbot-input button {
        width: 45px;
        height: 45px;
        margin-left: 12px;
        background: linear-gradient(135deg, #4e73df, #3b5bdb);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s;
        box-shadow: 0 3px 10px rgba(78, 115, 223, 0.2);
        font-size: 16px;
    }

    .chatbot-input button:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
    }

    /* Animation for messages */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message {
        animation: fadeIn 0.3s ease;
    }

    /* Room card styling for bot responses */
    .room-card {
        margin-bottom: 14px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        background: white;
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .room-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-color: rgba(78, 115, 223, 0.15);
    }

    .room-card-image {
        width: 100%;
        height: 140px;
        object-fit: cover;
        transition: transform 0.8s ease;
        position: relative;
    }

    .room-card:hover .room-card-image {
        transform: scale(1.05);
    }

    .room-card-image-container {
        overflow: hidden;
        position: relative;
    }

    .room-card-image-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.3) 100%);
        pointer-events: none;
    }

    .room-card-body {
        padding: 14px;
        position: relative;
        border-top: 1px solid rgba(0, 0, 0, 0.03);
    }

    .room-card-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #333;
        line-height: 1.4;
    }

    .room-card-price {
        color: #e74a3b;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }

    .room-card-price::before {
        content: '₫';
        margin-right: 2px;
        font-size: 12px;
    }

    .room-card-location {
        font-size: 12.5px;
        color: #555;
        margin-bottom: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
    }

    .room-card-location i {
        color: #5a78e4;
        margin-right: 4px;
        font-size: 12px;
    }

    .room-card-stats {
        display: flex;
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        padding-top: 5px;
        border-top: 1px dashed rgba(0, 0, 0, 0.07);
    }

    .room-card-stats span {
        margin-right: 12px;
        display: flex;
        align-items: center;
    }

    .room-card-stats i {
        margin-right: 4px;
        color: #5a78e4;
    }

    .room-card-link {
        display: inline-block;
        padding: 8px 14px;
        background: linear-gradient(135deg, #4e73df, #3b5bdb);
        color: white;
        text-decoration: none;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 10px;
        transition: all 0.3s;
        box-shadow: 0 2px 6px rgba(78, 115, 223, 0.2);
        letter-spacing: 0.3px;
    }

    .room-card-link:hover {
        background: linear-gradient(135deg, #3b5bdb, #2a428c);
        color: white;
        box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
        transform: translateY(-1px);
    }

    .room-card-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(231, 74, 59, 0.9);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .room-card-badge.featured {
        background: rgba(28, 200, 138, 0.9);
    }

    .room-card-badge.new {
        background: rgba(54, 185, 204, 0.9);
    }

    .typing-indicator {
        display: flex;
        margin-bottom: 15px;
        align-items: center;
        padding: 10px 16px;
        background: white;
        border-radius: 20px;
        width: fit-content;
        position: relative;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }

    .typing-indicator::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: -8px;
        width: 16px;
        height: 16px;
        background: white;
        clip-path: polygon(0 100%, 100% 100%, 100% 0);
        border-left: 1px solid rgba(0, 0, 0, 0.04);
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }

    .typing-indicator span {
        height: 10px;
        width: 10px;
        background: linear-gradient(135deg, #4e73df, #3b5bdb);
        border-radius: 50%;
        display: inline-block;
        margin: 0 3px;
        opacity: 0.6;
    }

    .typing-indicator span:nth-child(1) {
        animation: bounce 1.2s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation: bounce 1.2s infinite 0.2s;
        background: linear-gradient(135deg, #5a7ae4, #4e73df);
    }

    .typing-indicator span:nth-child(3) {
        animation: bounce 1.2s infinite 0.4s;
        background: linear-gradient(135deg, #6684ea, #5a7ae4);
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-5px);
        }

        50% {
            transform: translateY(0);
        }
    }

    /* Add subtle glow effect to chatbot toggle when there's activity */
    @keyframes glowing {
        0% {
            box-shadow: 0 0 10px rgba(78, 115, 223, 0.6);
        }

        50% {
            box-shadow: 0 0 20px rgba(78, 115, 223, 0.8), 0 0 30px rgba(78, 115, 223, 0.4);
        }

        100% {
            box-shadow: 0 0 10px rgba(78, 115, 223, 0.6);
        }
    }

    .chatbot-toggle.active {
        animation: glowing 2s infinite;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatToggle = document.getElementById('chatToggle');
        const chatBox = document.getElementById('chatBox');
        const minimizeChat = document.getElementById('minimizeChat');
        const closeChat = document.getElementById('closeChat');
        const chatInput = document.getElementById('chatInput');
        const sendMessage = document.getElementById('sendMessage');
        const chatMessages = document.getElementById('chatMessages');

        // Add click event listeners to all suggestion items
        function initSuggestionItems() {
            const suggestionItems = document.querySelectorAll('.suggestion-item');
            suggestionItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Get the text content without the quotes
                    const suggestionText = this.textContent.replace(/["]/g, '');
                    // Set the input value to the suggestion text
                    chatInput.value = suggestionText;
                    // Focus on the input
                    chatInput.focus();

                    // Optional: Add a visual effect to show the suggestion was clicked
                    this.style.backgroundColor = 'rgba(78, 115, 223, 0.1)';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 300);
                });
            });
        }

        // Initialize suggestion items
        initSuggestionItems();

        // Toggle chat box with animation
        chatToggle.addEventListener('click', function() {
            chatBox.style.display = 'flex';
            chatToggle.classList.add('active');

            // Slight delay before hiding toggle button for smooth transition
            setTimeout(() => {
                chatToggle.style.display = 'none';
            }, 200);

            // Animate entrance of chat box
            chatBox.style.opacity = '0';
            chatBox.style.transform = 'translateY(20px)';

            setTimeout(() => {
                chatBox.style.opacity = '1';
                chatBox.style.transform = 'translateY(0)';
            }, 50);

            scrollToBottom();

            // Focus input after animation completes
            setTimeout(() => {
                chatInput.focus();
            }, 400);
        });

        // Minimize chat with animation
        minimizeChat.addEventListener('click', function() {
            chatBox.style.opacity = '0';
            chatBox.style.transform = 'translateY(20px)';

            setTimeout(() => {
                chatBox.style.display = 'none';
                chatToggle.style.display = 'flex';
                // Reset chatBox styles for next opening
                chatBox.style.opacity = '';
                chatBox.style.transform = '';
            }, 300);
        });

        // Close chat with animation
        closeChat.addEventListener('click', function() {
            chatBox.style.opacity = '0';
            chatBox.style.transform = 'translateY(20px)';

            setTimeout(() => {
                chatBox.style.display = 'none';
                chatToggle.style.display = 'flex';
                chatToggle.classList.remove('active');
                // Reset chatBox styles for next opening
                chatBox.style.opacity = '';
                chatBox.style.transform = '';
            }, 300);
        });

        // Send message when button is clicked
        sendMessage.addEventListener('click', function() {
            sendUserMessage();
        });

        // Send message when Enter is pressed
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendUserMessage();
            }
        });

        // Function to send user message
        function sendUserMessage() {
            const message = chatInput.value.trim();
            if (message) {
                // Add user message to chat
                addMessage(message, 'user');

                // Clear input
                chatInput.value = '';

                // Show typing indicator
                showTypingIndicator();

                // Send message to server
                processMessage(message);
            }
        }

        // Add message to chat with enhanced animation
        function addMessage(message, sender, isHtml = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;

            // Add initial styles for animation
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = sender === 'user' ? 'translateX(10px)' : 'translateX(-10px)';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';

            if (isHtml) {
                contentDiv.innerHTML = message;
            } else {
                const p = document.createElement('p');
                p.textContent = message;
                contentDiv.appendChild(p);
            }

            // Add timestamp for messages
            if (sender === 'user') {
                const timestamp = document.createElement('div');
                timestamp.className = 'message-timestamp';
                timestamp.textContent = new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                timestamp.style.fontSize = '10px';
                timestamp.style.marginTop = '5px';
                timestamp.style.opacity = '0.7';
                timestamp.style.textAlign = 'right';
                contentDiv.appendChild(timestamp);
            }

            messageDiv.appendChild(contentDiv);
            chatMessages.appendChild(messageDiv);

            // Trigger animation
            setTimeout(() => {
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateX(0)';
                messageDiv.style.transition = 'all 0.3s ease-out';
            }, 50);

            scrollToBottom();

            // Add visual feedback when bot produces room cards
            if (isHtml && message.includes('room-card')) {
                // Add small delay for loading effect
                const loadingElements = contentDiv.querySelectorAll('.room-card');
                loadingElements.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(15px)';
                    card.style.transition = 'all 0.4s ease-out';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 300 + (index * 150)); // Stagger the animations
                });
            }

            // Add click handlers to any suggestion items in this message
            if (sender === 'bot') {
                setTimeout(() => {
                    const newSuggestionItems = contentDiv.querySelectorAll('.suggestion-item');
                    newSuggestionItems.forEach(item => {
                        item.addEventListener('click', function() {
                            const suggestionText = this.textContent.replace(/["]/g, '');
                            chatInput.value = suggestionText;
                            chatInput.focus();

                            this.style.backgroundColor = 'rgba(78, 115, 223, 0.1)';
                            setTimeout(() => {
                                this.style.backgroundColor = '';
                            }, 300);
                        });
                    });
                }, 100);
            }
        }

        // Show typing indicator with enhanced animation
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot';
            typingDiv.id = 'typingIndicator';
            typingDiv.style.opacity = '0';
            typingDiv.style.transform = 'translateY(10px)';

            typingDiv.innerHTML = `
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;

            // Animate in the typing indicator
            setTimeout(() => {
                typingDiv.style.opacity = '1';
                typingDiv.style.transform = 'translateY(0)';
                typingDiv.style.transition = 'all 0.3s ease-out';
            }, 50);

            chatMessages.appendChild(typingDiv);
            scrollToBottom();
        }

        // Remove typing indicator
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Scroll to bottom of chat
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Process message with server - enhanced with better feedback
        function processMessage(message) {
            // Track the start time to calculate response time
            const startTime = new Date();

            // Disable input and button while processing
            chatInput.disabled = true;
            sendMessage.disabled = true;

            // Add subtle animation to send button
            sendMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Create a timeout for slow responses
            const timeoutId = setTimeout(() => {
                const existingIndicator = document.getElementById('typingIndicator');
                if (existingIndicator) {
                    const timeoutMsg = document.createElement('div');
                    timeoutMsg.className = 'typing-note';
                    timeoutMsg.innerHTML = '<i class="fas fa-info-circle"></i> Đang xử lý câu hỏi phức tạp...';
                    timeoutMsg.style.fontSize = '11px';
                    timeoutMsg.style.color = '#777';
                    timeoutMsg.style.marginTop = '5px';
                    timeoutMsg.style.marginLeft = '5px';
                    existingIndicator.appendChild(timeoutMsg);
                }
            }, 3000); // Show message if response takes more than 3 seconds

            // Send message to server
            fetch('/components/chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Clear the timeout
                    clearTimeout(timeoutId);

                    // Calculate response time
                    const responseTime = new Date() - startTime;
                    console.log(`Chatbot response time: ${responseTime}ms`);

                    // Remove typing indicator
                    removeTypingIndicator();

                    // Add slight delay before showing response for natural feeling
                    setTimeout(() => {
                        // Add bot response
                        addMessage(data.response, 'bot', true);

                        // Add subtle glow to chatbot toggle if minimized
                        if (chatBox.style.display === 'none') {
                            chatToggle.classList.add('active');
                            setTimeout(() => {
                                chatToggle.classList.remove('active');
                            }, 3000);
                        }
                    }, Math.min(400, responseTime / 5)); // Proportional delay, but max 400ms
                })
                .catch(error => {
                    console.error('Error:', error);
                    clearTimeout(timeoutId);
                    removeTypingIndicator();

                    // Show a more friendly error message
                    const errorMsg = `
                    <p><i class="fas fa-exclamation-triangle" style="color: #e74a3b;"></i> Xin lỗi, có lỗi xảy ra khi xử lý yêu cầu của bạn.</p>
                    <p style="font-size: 12px; opacity: 0.8;">Vui lòng thử lại sau hoặc làm mới trang.</p>
                    `;
                    addMessage(errorMsg, 'bot', true);
                })
                .finally(() => {
                    // Re-enable input and button
                    chatInput.disabled = false;
                    sendMessage.disabled = false;
                    sendMessage.innerHTML = '<i class="fas fa-paper-plane"></i>';
                    chatInput.focus();
                });
        }
    });
</script>
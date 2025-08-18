<!-- chatbot.php -->
<style>
#chatbot-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 999;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    animation: pulse 2s infinite ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

#chatbot-box {
    display: none;
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    overflow: hidden;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

#chatbot-header {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    padding-bottom: 70px; /*  thêm padding để tạo khoảng cách an toàn phía dưới */
    background: #f7f9fc;
    font-size: 14px;
}

.chat-message {
    margin-bottom: 12px;
    max-width: 85%;
    padding: 10px;
    border-radius: 10px;
    line-height: 1.4;
}

.chat-message.user {
    background: #d4edda;
    align-self: flex-end;
    margin-left: auto;
}

.chat-message.bot {
    background: #e9f0ff;
    align-self: flex-start;
    margin-right: auto;
}

#chatbot-input {
    display: flex;
    border-top: 1px solid #ddd;
    padding: 8px;
    background: white;
}

#chatInput {
    flex: 1;
    border: none;
    padding: 8px;
    outline: none;
    font-size: 14px;
}

#sendChat {
    background: #007bff;
    color: white;
    border: none;
    padding: 0 15px;
    cursor: pointer;
    font-size: 16px;
}

/* Responsive cho mobile */
@media (max-width: 480px) {
    #chatbot-box {
        width: 90vw;
        height: 70vh;
        right: 5vw;
        bottom: 90px;
    }
}

.chat-message ul {
    padding-left: 20px;
    margin: 8px 0;
}
.chat-message p {
    margin-bottom: 10px;
}
</style>

<!-- Nút mở chatbot -->
<button id="chatbot-toggle" title="Chat với chúng tôi">
    <img src="images/image.png" alt="Chatbot Icon">
</button>

<!-- Khung chatbot -->
<div id="chatbot-box">
    <div id="chatbot-header">
        Trợ lý chatbot
        <span style="cursor:pointer;" onclick="$('#chatbot-box').fadeOut(200)">✖</span>
    </div>
    <div id="chatbot-messages">
        <div class="chat-message bot"> Thế giới ăn vặt xin chào, mình có thể tư vấn cho bạn về sản phẩm, voucher, chính sách vận chuyển, phí vận chuyển, phương thức thanh toán,địa chỉ, giờ mở cửa,...</div>
    </div>
    <div id="chatbot-input">
        <input type="text" id="chatInput" placeholder="Nhập câu hỏi..." autocomplete="off">
        <button id="sendChat">Gửi</button>
    </div>
</div>

<!-- Script -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
function appendMessage(message, type = 'bot') {
    const msg = $('<div class="chat-message"></div>').addClass(type).html(message);
    $('#chatbot-messages').append(msg);
    $('#chatbot-messages').scrollTop($('#chatbot-messages')[0].scrollHeight);
}

function formatReply(text) {
    // Chuyển * hoặc - đầu dòng thành danh sách <li>
    text = text.replace(/(?:^|\n)[\*\-]\s*(.*?)(?=\n|$)/g, '<li>$1</li>');
    if (text.includes('<li>')) {
        text = '<ul>' + text + '</ul>';
    }

    // Chuyển 2 dòng trống thành đoạn mới
    text = text.replace(/\n{2,}/g, '</p><p>');

    // Dòng đơn lẻ -> <br>
    text = text.replace(/\n/g, '<br>');

    // Bao bọc toàn bộ nội dung nếu chưa có <ul>
    if (!text.includes('<ul>')) {
        text = '<p>' + text + '</p>';
    }

    return text;
}

function sendMessage() {
    const message = $('#chatInput').val().trim();
    if (!message) return;

    appendMessage(message, 'user');
    $('#chatInput').val('').focus();

    $.post('/ShopAnVat/api/api_chatbot.php', { question: message }, function (res) {
        if (res && res.answer) {
            const formatted = formatReply(res.answer);
            appendMessage(formatted, 'bot');
        } else {
            appendMessage(' Bot không phản hồi.', 'bot');
        }
    }, 'json').fail(function () {
        appendMessage(' Lỗi kết nối với chatbot.', 'bot');
    });
}

$(document).ready(function () {
    $('#chatbot-toggle').on('click', function () {
        $('#chatbot-box').fadeToggle(200);
        $('#chatInput').focus();
    });

    $('#sendChat').on('click', sendMessage);

    $('#chatInput').on('keypress', function (e) {
        if (e.which === 13) {
            sendMessage();
            return false;
        }
    });

    $('#chatbot-box').hide();
});
</script>

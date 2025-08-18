const express = require('express');
const cors = require('cors');
const axios = require('axios');

const app = express();
const PORT = 3001;

app.use(cors());
app.use(express.json());

// Endpoint nhận POST từ Postman hoặc client
app.post('/', async (req, res) => {
    const { question } = req.body;

    if (!question) {
        return res.status(400).json({ error: 'Thiếu câu hỏi' });
    }

    try {
        // Gọi đến Gemini API thật hoặc mock
        const answer = `Giả lập: Trái đất quay quanh mặt trời mất khoảng 365 ngày.`; // Mô phỏng
        res.json({ answer });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Lỗi khi gọi Gemini API' });
    }
});

// Khởi động server
app.listen(PORT, () => {
    console.log(`Middleware Gemini server đang chạy tại http://localhost:${PORT}`);
});

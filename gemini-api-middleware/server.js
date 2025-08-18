const express = require('express');
const axios = require('axios');
const cors = require('cors');
const app = express();
app.use(express.json());
app.use(cors());

const API_KEY = process.env.GEMINI_API_KEY || 'AIzaSyDIl__lKb4jj8qtMw6n9RvOcyJIY5No8lQ';

const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${API_KEY}`;


app.post('/ask', async (req, res) => {
  const question = req.body.question;
  if (!question) {
    return res.status(400).json({ error: 'Thiếu nội dung câu hỏi' });
  }

  try {
    const response = await axios.post(GEMINI_API_URL, {
      contents: [{ parts: [{ text: question }] }]
    });

    const reply = response.data.candidates[0].content.parts[0].text;
    res.json({ reply });
  } catch (err) {
    console.error('[Gemini Error]', err.response?.data || err.message);
    res.status(500).json({ error: 'Lỗi khi gọi Gemini API' });
  }
});

app.listen(3001, () => {
  console.log('✅ Gemini middleware server chạy tại http://localhost:3001');
});

const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const app = express();
const PORT = 3000;

// Создаем папки для хранения
const uploadsDir = path.join(__dirname, 'uploads');
const thumbsDir = path.join(__dirname, 'uploads', 'thumbs');

if (!fs.existsSync(uploadsDir)) fs.mkdirSync(uploadsDir, { recursive: true });
if (!fs.existsSync(thumbsDir)) fs.mkdirSync(thumbsDir, { recursive: true });

// Настройка multer для загрузки файлов
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    if (file.mimetype.startsWith('video/')) {
      cb(null, uploadsDir);
    } else if (file.mimetype.startsWith('image/')) {
      cb(null, thumbsDir);
    }
  },
  filename: function (req, file, cb) {
    const uniqueName = Date.now() + '-' + Math.round(Math.random() * 1E9) + path.extname(file.originalname);
    cb(null, uniqueName);
  }
});

const upload = multer({ 
  storage: storage,
  limits: { fileSize: 500 * 1024 * 1024 } // 500MB
});

// База данных видео (в реальном проекте используйте MongoDB/PostgreSQL)
let videosDB = [
  {
    id: '1',
    title: 'ТХК Патриот - сила',
    description: 'Музыкальный клип',
    videoFile: 'video1.mp4',
    thumbnail: 'thumb1.jpg',
    author: 'thcpatriot',
    authorAvatar: 'https://yt3.ggpht.com/SzQ7y98PMEoYeLYjBP2e9mC_eItlh1wOdP5H0n0QtZViTJ8OKDlnAOdUv2TiXTmfmkLFj3o2=s600-c-k-c0x00ffffff-no-rj-rp-mo',
    views: 34,
    uploadDate: '2025-06-04T17:08:49+03:00'
  }
];

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static('public'));
app.use('/uploads', express.static('uploads'));

// API для получения списка видео
app.get('/api/videos', (req, res) => {
  res.json(videosDB);
});

// API для получения конкретного видео
app.get('/api/video/:id', (req, res) => {
  const video = videosDB.find(v => v.id === req.params.id);
  if (video) {
    res.json(video);
  } else {
    res.status(404).json({ error: 'Video not found' });
  }
});

// API для загрузки видео
app.post('/api/upload', upload.fields([
  { name: 'video', maxCount: 1 },
  { name: 'thumbnail', maxCount: 1 }
]), (req, res) => {
  try {
    const { title, description, author } = req.body;
    
    if (!req.files || !req.files.video) {
      return res.status(400).json({ error: 'No video file uploaded' });
    }

    const newVideo = {
      id: Date.now().toString(),
      title: title || 'Без названия',
      description: description || '',
      videoFile: req.files.video[0].filename,
      thumbnail: req.files.thumbnail ? req.files.thumbnail[0].filename : null,
      author: author || 'Anonymous',
      authorAvatar: 'https://yt3.ggpht.com/SzQ7y98PMEoYeLYjBP2e9mC_eItlh1wOdP5H0n0QtZViTJ8OKDlnAOdUv2TiXTmfmkLFj3o2=s600-c-k-c0x00ffffff-no-rj-rp-mo',
      views: 0,
      uploadDate: new Date().toISOString()
    };

    videosDB.unshift(newVideo); // Добавляем в начало
    res.json({ success: true, video: newVideo });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// API для увеличения просмотров
app.post('/api/video/:id/view', (req, res) => {
  const video = videosDB.find(v => v.id === req.params.id);
  if (video) {
    video.views += 1;
    res.json({ success: true, views: video.views });
  } else {
    res.status(404).json({ error: 'Video not found' });
  }
});

// Страница видео
app.get('/video/:id', (req, res) => {
  const video = videosDB.find(v => v.id === req.params.id);
  if (video) {
    res.sendFile(path.join(__dirname, 'public', 'video.html'));
  } else {
    res.status(404).send('Video not found');
  }
});

// Главная страница
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Запуск сервера
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
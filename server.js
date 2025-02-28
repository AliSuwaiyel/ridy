require('dotenv').config();
const express = require('express');
const path = require('path');
const cors = require('cors');
const { Telegraf, Scenes, session } = require('telegraf');
const mysql = require('mysql2/promise');
const { OpenAI } = require('openai');

// Initialize Express app
const app = express();
app.use(cors({ origin: 'http://localhost:3000' }));
app.use(express.json());
app.use(express.static('public'));

// Database configuration
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'AliBinMySQL7858@',
    database: process.env.DB_NAME || 'ridy',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// OpenAI configuration
const openai = new OpenAI({
    apiKey: process.env.OPENAI_API_KEY || 'your-openai-key-here'
});

// Telegram Bot Setup
const bot = new Telegraf(process.env.BOT_TOKEN || '8140929653:AAG__-8niolWi2-zPLKwdSsRw7D96ezyOYM');

// ================== Telegram Bot Logic ==================
const { Stage } = Scenes;
const orderScene = new Scenes.BaseScene('order');
const phoneScene = new Scenes.BaseScene('phone');
const nameScene = new Scenes.BaseScene('name');

// Database functions
async function insertOrder(type, amount, size, chatId, orderInfo, specifications) {
    const conn = await pool.getConnection();
    try {
        await conn.query(
            `INSERT INTO orders 
            (category, amount, order_size, user_id, chat_date, order_info, specifications)
            VALUES (?, ?, ?, ?, NOW(), ?, ?)`,
            [type, amount, size, chatId, orderInfo, specifications]
        );
    } finally {
        conn.release();
    }
}

async function checkPhone(userId) {
    const conn = await pool.getConnection();
    try {
        const [rows] = await conn.query(
            'SELECT customer_phone FROM customers WHERE user_id = ?',
            [userId]
        );
        return rows.length > 0;
    } finally {
        conn.release();
    }
}

async function saveUser(userId, phone, name) {
    const conn = await pool.getConnection();
    try {
        await conn.query(
            'INSERT INTO customers (user_id, customer_phone, user_first_name) VALUES (?, ?, ?)',
            [userId, phone, name]
        );
    } finally {
        conn.release();
    }
}

async function getHistory(chatId) {
    const conn = await pool.getConnection();
    try {
        const [orders] = await conn.query(
            `SELECT category, amount FROM orders 
            WHERE user_id = ? 
            ORDER BY chat_date DESC LIMIT 3`,
            [chatId]
        );
        return orders.map((o, i) => `${i+1}: ${o.category} - ${o.amount}`).join('\n');
    } finally {
        conn.release();
    }
}

// AI processing function
async function sendToAI(message) {
    const response = await openai.chat.completions.create({
        model: "gpt-3.5-turbo",
        messages: [{
            role: "system",
            content: `Generate output in format: 'FOOD TYPE', 'SIZE', 'AMOUNT(return as a number)', 'SPECIFICATION'. Rules:
            1. Never translate Arabic to English
            2. Combine multi-word foods with spaces
            3. Default size to 'عادي' if missing
            4. Default specification to 'none'
            5. Exactly four comma-separated parameters
            6. If amount is not defined you should guess based on the words`
            
        }, {
            role: "user",
            content: `split into kind of food food size and amount: ${message}`
        }]
    });
    return response.choices[0].message.content;
}

// Conversation scenes configuration
nameScene.enter(async (ctx) => {
    await ctx.reply('👋 مرحبًا!\n قبل الطلب، زودنا باسمك الأول ✍️');
});

nameScene.on('text', async (ctx) => {
    ctx.session.name = ctx.message.text.trim();
    await ctx.scene.enter('phone');
});

phoneScene.enter(async (ctx) => {
    await ctx.reply(`شكرًا ${ctx.session.name}! الآن، زودنا برقم جوالك 📱`);
});

phoneScene.on('text', async (ctx) => {
    const phone = ctx.message.text.trim();
    if (!/^05\d{8}$/.test(phone)) {
        await ctx.reply('❌ رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام');
        return;
    }
    
    await saveUser(ctx.from.id, phone, ctx.session.name);
    await ctx.reply(`شكرًا ${ctx.session.name}✨\nوش طلبك اليوم😋`);
    await ctx.scene.enter('order');
});


orderScene.on('text', async (ctx) => {
    try {
        const response = await sendToAI(ctx.message.text);
        const [type, size, amount, spec] = response.split(',').map(s => s.trim());
        
        await insertOrder(type, amount, size, ctx.from.id, ctx.message.text, spec);
        await ctx.reply(
            `نوع الأكل: ${type}\nالحجم: ${size}\nالكمية: ${amount}\nالتفضيلات: ${spec}\n\nشكراً لك! ✨`
        );
        await ctx.scene.leave();
    } catch (err) {
        console.error(err);
        await ctx.reply('حدث خطأ. من فضلك حاول مرة أخرى.');
    }
});

const stage = new Scenes.Stage([nameScene, phoneScene, orderScene]);
bot.use(session());
bot.use(stage.middleware());

// Bot command handlers
bot.command('start', (ctx) => 
    ctx.reply('حياك الله في RIDY \nللطلب اختر Start Order من قائمة الخيارات \n🍔🍟🍕🌯!')
);

bot.command('history', async (ctx) => {
    try {
        const history = await getHistory(ctx.from.id);
        await ctx.reply(`آخر 3 طلبات:\n${history}`);
    } catch (err) {
        console.error(err);
        await ctx.reply('حدث خطأ في استرجاع السجل.');
    }
});

bot.command('help', async (ctx) => {
    
        await ctx.reply('TBC');
    
});

bot.command('order', async (ctx) => {
    try {
        const hasPhone = await checkPhone(ctx.from.id);
        if (hasPhone) {
            const [user] = await pool.query(
                'SELECT user_first_name FROM customers WHERE user_id = ?',
                [ctx.from.id]
            );
            await ctx.reply(`مرحبا ${user[0].user_first_name}\n وش حاب تطلب اليوم 😋`);
            await ctx.scene.enter('order');
            if (ctx.message.text.startsWith('/')) return; // Ignore commands like /cancel inside text handling

        } else {
            await ctx.scene.enter('name');
        }
    } catch (err) {
        console.error(err);
        await ctx.reply('حدث خطأ في بدء الطلب.');
    }
});

// Cancel command to exit the order process
// Global cancel command to exit any scene
bot.command('cancel', (ctx) => {
    ctx.reply(' تم إلغاء الطلب. إذا احتجت أي شيء ثاني، كلمنا 😊');
    ctx.scene.leave();
});

// Ensure cancel works inside order scene
orderScene.command('cancel', async (ctx) => {
    await ctx.reply(' تم إلغاء الطلب.');
    await ctx.scene.leave();
});

// Modify order scene to ignore /cancel as an order
orderScene.on('text', async (ctx) => {
    if (ctx.message.text.startsWith('/')) return; // Ignore commands like /cancel inside text handling
    
    try {
        const response = await sendToAI(ctx.message.text);
        const [type, size, amount, spec] = response.split(',').map(s => s.trim());
        
        await insertOrder(type, amount, size, ctx.from.id, ctx.message.text, spec);
        await ctx.reply(
            `نوع الأكل: ${type}\nالحجم: ${size}\nالكمية: ${amount}\nالتفضيلات: ${spec}\n\nشكراً لك! ✨`
        );
        await ctx.scene.leave();
    } catch (err) {
        console.error(err);
        await ctx.reply('حدث خطأ. من فضلك حاول مرة أخرى.');
    }
});



// Message handling
bot.on('text', (ctx) => {
    const text = ctx.message.text.toLowerCase();
    if (text.includes('مرحبا') || text.includes('الو')) {
        return ctx.reply('تقدر ترسل "طلب" عشان تبدأ طلب جديد 😋!');
    }
    ctx.reply('مافهمت');
});

// ================== Express Server Routes ==================
app.get('/approve-order', async (req, res) => {
    try {
        const userId = req.query.user_id;
        if (!userId || !/^\d+$/.test(userId)) {
            return res.status(400).json({ error: "User ID مطلوب أو غير صالح" });
        }

        await bot.telegram.sendMessage(userId, "تم قبول طلبك ✅!");
        await bot.telegram.sendMessage(userId, "بيكون جاهز خلال 20 دقيقة");

        res.json({ success: true, message: "تم إرسال الموافقة إلى المستخدم!" });
    } catch (err) {
        console.error("Error sending message:", err);
        res.status(500).json({ 
            success: false,
            error: `فشل إرسال الرسالة: ${err.message}`
        });
    }
});

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'web-dash.php'));
});

// ================== Start Services ==================
const PORT = process.env.PORT || 8000;
app.listen(PORT, () => console.log(`HTTP Server running on http://localhost:${PORT}`));
bot.launch().then(() => console.log('Telegram Bot started'));

// Graceful shutdown handling
process.once('SIGINT', () => {
    bot.stop('SIGINT');
    pool.end();
    process.exit();
});

process.once('SIGTERM', () => {
    bot.stop('SIGTERM');
    pool.end();
    process.exit();
});
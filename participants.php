<?php
// ملف شريط التنقل العلوي المشترك بين الصفحات
session_start();
?>

<nav class="admin-navbar">
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-quran"></i>
            <span>مسابقة القرآن الكريم</span>
        </div>
        
        <ul class="nav-links">
            <li>
                <a href="admin_dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i> <span>لوحة التحكم</span>
                </a>
            </li>
            <li>
                <a href="participants.php" <?php echo basename($_SERVER['PHP_SELF']) == 'participants.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> <span>المشاركون</span>
                </a>
            </li>
            <li>
                <a href="results.php" <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-bar"></i> <span>النتائج</span>
                </a>
            </li>
            <li>
                <a href="settings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i> <span>الإعدادات</span>
                </a>
            </li>
        </ul>
        
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo isset($_SESSION['admin_username']) ? strtoupper(substr($_SESSION['admin_username'], 0, 1)) : 'A'; ?>
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fas fa-user-cog"></i> الملف الشخصي</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                </div>
            </div>
            <?php if(isset($_SESSION['admin_username'])): ?>
                <span class="username"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3a0ca3;
    --accent-color: #f72585;
    --gold-color: #ffd700;
    --text-light: #f8f9fa;
    --text-dark: #212529;
    --bg-light: #ffffff;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.admin-navbar {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: var(--text-light);
    padding: 0;
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 1000;
    font-family: 'Tajawal', sans-serif;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
    height: 70px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-light);
    transition: all 0.3s ease;
}

.logo i {
    font-size: 1.8rem;
    color: var(--gold-color);
    transition: transform 0.3s;
}

.logo:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.logo:hover i {
    transform: rotate(-15deg);
}

.nav-links {
    display: flex;
    list-style: none;
    margin: 0;
    height: 100%;
    gap: 5px;
}

.nav-links li {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    padding: 0 1.5rem;
    height: 100%;
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    border-radius: 4px;
}

.nav-links a i {
    margin-left: 8px;
    font-size: 1.1rem;
    transition: all 0.3s;
}

.nav-links a:hover {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.nav-links a.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    font-weight: 700;
}

.nav-links a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background-color: var(--gold-color);
    transition: width 0.3s ease, opacity 0.3s;
    opacity: 0;
}

.nav-links a:hover::after,
.nav-links a.active::after {
    width: 70%;
    opacity: 1;
}

.user-menu {
    position: relative;
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-color), #fad0c4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
}

.username {
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
}

.dropdown-menu {
    position: absolute;
    top: 120%;
    right: 0;
    background: var(--bg-light);
    border-radius: 8px;
    box-shadow: var(--shadow);
    width: 200px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    z-index: 100;
    transform: translateY(10px);
    padding: 0.5rem 0;
}

.user-avatar:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    padding: 0.8rem 1.2rem;
    color: var(--text-dark);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.dropdown-menu a i {
    margin-left: 0.5rem;
    color: var(--primary-color);
    font-size: 1rem;
}

.dropdown-menu a:hover {
    background: #f8f9fa;
    color: var(--primary-color);
    padding-right: 1.5rem;
}

.dropdown-menu a:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.dropdown-menu a:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}

/* تصميم متجاوب */
@media (max-width: 992px) {
    .nav-container {
        padding: 0 1.5rem;
    }
    
    .nav-links a {
        padding: 0 1rem;
        font-size: 1rem;
    }
    
    .logo {
        font-size: 1.2rem;
    }
    
    .logo i {
        font-size: 1.5rem;
    }
    
    .username {
        display: none;
    }
}

@media (max-width: 768px) {
    .nav-container {
        padding: 0 1rem;
        height: 60px;
    }
    
    .logo span {
        display: none;
    }
    
    .nav-links a span {
        display: none;
    }
    
    .nav-links a i {
        margin-left: 0;
        font-size: 1.3rem;
    }
    
    .nav-links a {
        padding: 0 0.8rem;
    }
    
    .user-avatar {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .nav-links {
        gap: 2px;
    }
    
    .nav-links a {
        padding: 0 0.6rem;
    }
    
    .dropdown-menu {
        width: 180px;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
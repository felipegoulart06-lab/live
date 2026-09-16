<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class SqliteSchema
{
    public static function install(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = OFF');

        foreach (self::tables() as $sql) {
            $pdo->exec($sql);
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    public static function isInstalled(PDO $pdo): bool
    {
        $row = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();

        return (bool) $row;
    }

    /** @return array<int, string> */
    private static function tables(): array
    {
        return [
            'CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                group_name TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            )',
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                account_type TEXT NOT NULL DEFAULT \'buyer\',
                status TEXT NOT NULL DEFAULT \'pending\',
                email_verified_at TEXT,
                last_login_at TEXT,
                last_login_ip TEXT,
                last_seen_at TEXT,
                two_factor_enabled INTEGER NOT NULL DEFAULT 0,
                two_factor_secret TEXT,
                deleted_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (user_id, role_id)
            )',
            'CREATE TABLE IF NOT EXISTS profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                display_name TEXT NOT NULL,
                professional_name TEXT,
                slug TEXT NOT NULL UNIQUE,
                headline TEXT,
                bio TEXT,
                phone TEXT,
                city TEXT,
                state TEXT,
                country TEXT NOT NULL DEFAULT \'BR\',
                website TEXT,
                avatar_path TEXT,
                cover_path TEXT,
                experience_years INTEGER,
                response_rate REAL NOT NULL DEFAULT 0,
                avg_response_minutes INTEGER NOT NULL DEFAULT 0,
                orders_completed INTEGER NOT NULL DEFAULT 0,
                rating_avg REAL NOT NULL DEFAULT 0,
                rating_count INTEGER NOT NULL DEFAULT 0,
                is_verified INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                icon TEXT,
                image_path TEXT,
                short_description TEXT,
                description TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                is_featured INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                commission_percent REAL,
                meta_title TEXT,
                meta_description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS subcategories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                meta_title TEXT,
                meta_description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS skills (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS languages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT NOT NULL UNIQUE
            )',
            'CREATE TABLE IF NOT EXISTS user_skills (
                user_id INTEGER NOT NULL,
                skill_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, skill_id)
            )',
            'CREATE TABLE IF NOT EXISTS user_languages (
                user_id INTEGER NOT NULL,
                language_id INTEGER NOT NULL,
                level TEXT NOT NULL DEFAULT \'fluent\',
                PRIMARY KEY (user_id, language_id)
            )',
            'CREATE TABLE IF NOT EXISTS badges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                icon TEXT,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS user_badges (
                user_id INTEGER NOT NULL,
                badge_id INTEGER NOT NULL,
                awarded_at TEXT NOT NULL,
                PRIMARY KEY (user_id, badge_id)
            )',
            'CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                subcategory_id INTEGER,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                short_description TEXT NOT NULL,
                description TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                cover_path TEXT,
                video_url TEXT,
                starting_price_cents INTEGER NOT NULL DEFAULT 0,
                min_delivery_days INTEGER NOT NULL DEFAULT 1,
                orders_count INTEGER NOT NULL DEFAULT 0,
                views_count INTEGER NOT NULL DEFAULT 0,
                favorites_count INTEGER NOT NULL DEFAULT 0,
                rating_avg REAL NOT NULL DEFAULT 0,
                rating_count INTEGER NOT NULL DEFAULT 0,
                is_featured INTEGER NOT NULL DEFAULT 0,
                has_discount INTEGER NOT NULL DEFAULT 0,
                published_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS service_packages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL,
                tier TEXT NOT NULL,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                price_cents INTEGER NOT NULL,
                delivery_days INTEGER NOT NULL,
                revisions INTEGER NOT NULL DEFAULT 0,
                quantity INTEGER NOT NULL DEFAULT 1,
                benefits TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS service_extras (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                price_cents INTEGER NOT NULL,
                extra_days INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS service_images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL,
                path TEXT NOT NULL,
                alt_text TEXT,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS service_faqs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL,
                question TEXT NOT NULL,
                answer TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS favorites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                favoritable_type TEXT NOT NULL,
                favoritable_id INTEGER NOT NULL,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                public_code TEXT NOT NULL UNIQUE,
                buyer_id INTEGER NOT NULL,
                seller_id INTEGER NOT NULL,
                service_id INTEGER NOT NULL,
                package_id INTEGER,
                status TEXT NOT NULL DEFAULT \'awaiting_payment\',
                subtotal_cents INTEGER NOT NULL,
                extras_cents INTEGER NOT NULL DEFAULT 0,
                discount_cents INTEGER NOT NULL DEFAULT 0,
                fee_cents INTEGER NOT NULL DEFAULT 0,
                total_cents INTEGER NOT NULL,
                seller_amount_cents INTEGER NOT NULL,
                currency TEXT NOT NULL DEFAULT \'BRL\',
                delivery_days INTEGER NOT NULL,
                due_at TEXT,
                paid_at TEXT,
                completed_at TEXT,
                cancelled_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS wallets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                available_cents INTEGER NOT NULL DEFAULT 0,
                pending_cents INTEGER NOT NULL DEFAULT 0,
                reserved_cents INTEGER NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT \'BRL\',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS commission_rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                scope TEXT NOT NULL,
                category_id INTEGER,
                user_id INTEGER,
                percent REAL NOT NULL,
                fixed_cents INTEGER NOT NULL DEFAULT 0,
                withdraw_fee_cents INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS subscription_plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                price_cents INTEGER NOT NULL DEFAULT 0,
                billing_interval TEXT NOT NULL DEFAULT \'month\',
                max_services INTEGER,
                max_images INTEGER,
                max_portfolio INTEGER,
                commission_percent REAL,
                search_priority INTEGER NOT NULL DEFAULT 0,
                featured_home INTEGER NOT NULL DEFAULT 0,
                advanced_analytics INTEGER NOT NULL DEFAULT 0,
                badge_slug TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                content TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'published\',
                sort_order INTEGER NOT NULL DEFAULT 0,
                meta_title TEXT,
                meta_description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS faqs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                question TEXT NOT NULL,
                answer TEXT NOT NULL,
                placement TEXT NOT NULL DEFAULT \'both\',
                is_active INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS banners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                placement TEXT NOT NULL,
                title TEXT NOT NULL,
                subtitle TEXT,
                cta_label TEXT,
                cta_url TEXT,
                image_path TEXT,
                starts_at TEXT,
                ends_at TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS testimonials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                author_name TEXT NOT NULL,
                author_role TEXT,
                quote TEXT NOT NULL,
                rating INTEGER NOT NULL DEFAULT 5,
                avatar_path TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                status TEXT NOT NULL DEFAULT \'subscribed\',
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                setting_group TEXT NOT NULL DEFAULT \'general\',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS email_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                subject TEXT NOT NULL,
                body TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS help_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS help_articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                body TEXT NOT NULL,
                is_published INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS login_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                email_attempt TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                user_agent TEXT NOT NULL,
                success INTEGER NOT NULL,
                failure_reason TEXT,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS security_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                type TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                object_type TEXT NOT NULL,
                object_id INTEGER,
                old_values TEXT,
                new_values TEXT,
                ip_address TEXT NOT NULL,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                buyer_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                subcategory_id INTEGER,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT NOT NULL,
                budget_min_cents INTEGER,
                budget_max_cents INTEGER,
                deadline_days INTEGER,
                status TEXT NOT NULL DEFAULT \'open\',
                proposals_count INTEGER NOT NULL DEFAULT 0,
                published_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS proposals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                seller_id INTEGER NOT NULL,
                price_cents INTEGER NOT NULL,
                delivery_days INTEGER NOT NULL,
                message TEXT NOT NULL,
                description TEXT,
                status TEXT NOT NULL DEFAULT \'sent\',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS withdrawals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount_cents INTEGER NOT NULL,
                fee_cents INTEGER NOT NULL DEFAULT 0,
                method TEXT NOT NULL,
                pix_key TEXT,
                bank_name TEXT,
                bank_agency TEXT,
                bank_account TEXT,
                status TEXT NOT NULL DEFAULT \'requested\',
                reviewer_id INTEGER,
                notes TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS oauth_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                provider TEXT NOT NULL,
                provider_user_id TEXT NOT NULL,
                email TEXT,
                created_at TEXT NOT NULL,
                UNIQUE (provider, provider_user_id)
            )',
            'CREATE INDEX IF NOT EXISTS idx_services_status ON services(status, published_at)',
            'CREATE INDEX IF NOT EXISTS idx_services_cat ON services(category_id, status)',
            'CREATE INDEX IF NOT EXISTS idx_oauth_user ON oauth_accounts(user_id)',
        ];
    }
}

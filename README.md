# 🏪 ShopStock — Basic Inventory Management System

A full-stack inventory management system for a small shop built with:
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL (XAMPP)

---

## 🚀 Setup Instructions

1. Make sure **XAMPP** is installed and running (Apache + MySQL)
2. Place the project folder inside `C:\xampp\htdocs\`
3. Open your browser and go to:
   ```
   http://localhost/inventory ms project/Inventort-ms-project/setup.php
   ```
4. The setup page will create the database and insert sample data automatically.
5. After setup, click **Go to Dashboard** or visit:
   ```
   http://localhost/inventory ms project/Inventort-ms-project/index.php
   ```

---

## 📁 File Structure

```
Inventort-ms-project/
├── db.php              → Database connection
├── setup.php           → One-time DB setup + sample data
├── index.php           → Dashboard
├── products.php        → View / search all products
├── add_product.php     → Add a new product
├── edit_product.php    → Edit / delete a product
├── delete_product.php  → Delete handler
├── categories.php      → Manage categories
├── low_stock.php       → Low stock alert report
├── css/
│   └── style.css       → All styles
└── includes/
    └── sidebar.php     → Navigation sidebar
```

---

## ✨ Features

- 📊 **Dashboard** with summary stats (total products, stock value, low stock count)
- 📦 **Product management** — add, edit, delete with validation
- 🗂️ **Category management** — organize products
- 🔍 **Search & filter** products by name or category
- ↕️ **Sortable columns** in product table
- 📄 **Pagination** for large product lists
- ⚠️ **Low stock alerts** with shortage calculation
- 💰 **Live stock value preview** on add/edit forms
- 📱 **Responsive design** with collapsible sidebar

---

## 👨‍💻 Authors
- Amir Dedefo & Naol Getu

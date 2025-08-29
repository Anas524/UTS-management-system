# Universal Trade Services – Management System

A **Laravel-based management system** for Universal Trade Services.  
Currently includes an **Expense Tracker** module with row-level attachments, PDF bundling, and Excel export.  
Built to be scalable for future modules such as **Invoices, Customer Sheets, and Business Analytics**.

---

## 🚀 Features

- **Expense Tracker**
  - Add, edit, and delete expense rows
  - Attach files (invoices, receipts, notes)
  - View or download individual attachments
  - Merge multiple attachments into a single PDF
  - Export expense sheet to Excel

- **User Management**
  - Role-based access (Admin & User)
  - Authentication (Login/Register)

- **Tech Highlights**
  - Laravel 10 (PHP 8+)
  - File upload + storage handling
  - PDF generation with [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
  - PDF merging with [libmergepdf](https://github.com/psliwa/PHPPdf)
  - Responsive UI with Blade + jQuery

---

## 📂 Project Structure
uts-management-system/
├── app/ # Laravel application files
├── bootstrap/
├── config/
├── database/ # Migrations & seeders
├── public/ # Public index.php, assets
├── resources/ # Blade views, CSS, JS
├── routes/ # Web routes
├── storage/ # Logs, temp, and uploaded files
└── vendor/

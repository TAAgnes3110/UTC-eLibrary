# UTC eLibrary - Design System & Dashboard Summary

This document provides a comprehensive summary of the dashboards, components, styles, and functional logic implemented in the UTC eLibrary mockup (`utc-library-system.html`).

---

## 1. Design System Overview

The system uses a **Premium Modern Aesthetic** characterized by clean layouts, vibrant accent colors, and high-quality interaction states.

### Core Visual Tokens
*   **Colors**:
    *   `--utc-primary`: `#1e3a8a` (Indigo 900 / UTC Blue) - Used for primary branding, buttons, and admin sidebar.
    *   `--utc-secondary`: `#0ea5e9` (Sky 500) - Used for secondary highlights and librarian roles.
    *   `Background`: `#f8fafc` (Main) / `#F1F5F9` (Content areas).
    *   `Accents`: Emerald (Success/Active), Amber (Warning/Pending), Rose (Error/Overdue).
*   **Typography**:
    *   Font: `Plus Jakarta Sans` (weights 300 to 800).
    *   Style: High contrast between bold headings and legible body text.
*   **Shadows & Depth**:
    *   `.premium-shadow`: Multi-layered soft shadow for cards and modals (`box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.05), ...`).
    *   `Glassmorphism`: Used in headers (`backdrop-blur-xl`) and overlaying elements.
*   **Animations**:
    *   `animate-enter`: Fade-in and slide-up transition for page content.
    *   `Hover Effects`: Subtle scaling (`scale-105`), translations (`-translate-y-1`), and background transitions.

---

## 2. Dashboard Structures

The system dynamically switches layouts and features based on the user's **Role**.

### A. Admin Dashboard
*   **Purpose**: Full system oversight and heavy data management.
*   **Layout**: Vertical sidebar navigation (left) + Action-oriented header + Data-rich content area.
*   **Key Features**:
    *   High-level analytics (Chart.js integration).
    *   Full CRUD for all modules (Books, Users, Categories, etc.).
    *   Role switching for system preview.

### B. Librarian Dashboard
*   **Purpose**: Operational efficiency and front-desk library tasks.
*   **Layout**: Balanced between navigation and quick-action tools.
*   **Key Features**:
    *   Operational Hero Section: Quick stats on today's loans and overdue items.
    *   Transaction focus: "Xử Lý Mượn Trả" (Loan/Return processing).

### C. Student (Reader) Dashboard
*   **Purpose**: Resource discovery and personal library management.
*   **Layout**: Horizontal top navigation (sticky) + Sidebar for category/material type filtering.
*   **Key Features**:
    *   Visual Book Grid: Cover-focused cards.
    *   Material Type Filtering: Tabs for "Giáo trình", "Đồ án", "Tạp chí", etc.
    *   Advanced Search: Real-time filtering by year, author, and publisher.

---

## 3. UI Components Library

### Navigation Components
*   **Sidebar**: Collapsible (conceptually), multi-sectioned (Quản lý Tài Nguyên, Nghiệp Vụ, Hệ Thống).
*   **Top Header**: Context-aware title, Search bar, Role switcher (demo), and User profile dropdown.

### Data Display Components
*   **Interactive Tables**:
    *   Sticky headers.
    *   Striped/Hoverable rows.
    *   Action clusters (Edit, Delete, View).
    *   Status badges (Pill-shaped, color-coded).
*   **Stats Cards**: Large numbers with vibrant icons and descriptive labels.
*   **Book Cards**: 2:3 Aspect ratio covers, title clamping, hover-revealed details.

### Interaction Components (Modals)
*   **CRUD Modals**: Multi-column forms for adding/editing complex data (Books, Readers).
*   **Import Modal**: Specialized area for Excel drag-and-drop (`.xlsx`, `.csv`).
*   **Loan Processing Modal**: Searchable dropdown for adding multiple books to a single loan transaction.
*   **Delete Confirmation**: Standardized warning modal with destructive action styling.

### Form Elements
*   **Inputs**: Rounded-xl, focus-ring outlines, bold font.
*   **Selects**: Customized with `appearance-none` and Lucide icons.
*   **Search**: Real-time results dropdown in the loan processing flow.

---

## 4. Logical Modules (Mock Data Structures)

### Book Management
*   **Fields**: Title, Author, Category, Publisher, Year, Pages, Price, Quantity (Total/Remaining), Image, Description.
*   **Views**: Grid (Student), Table (Admin).

### Category & Material Types
*   **Material Types**: Mapping to specialized icons (Book, Layers, Graduation Cap, Microscope).
*   **Categories**: Grouping books by faculty (CNTT, Kinh tế, Cơ khí, etc.).

### Loan System
*   **States**: "Đang mượn" (Amber), "Đã trả" (Emerald).
*   **Logic**: Validates book selection, sets due dates, and tracks administrator/reader engagement.

### User & Card Management
*   **Readers**: Contact info and membership status tracking.
*   **Library Cards**: Digital/Physical card issuance (UTC prefix IDs).

---

## 5. Technology Stack (Frontend)
*   **Framework**: Tailwind CSS (Styling), Alpine.js (State & Interactivity).
*   **Icons**: Lucide Icons (Unified set).
*   **Charts**: Chart.js (Operational analytics).
*   **Fonts**: Google Fonts (Plus Jakarta Sans).

# AGENTS.md

## Project Name
LeadHunter AI

## Overview
LeadHunter AI adalah aplikasi sederhana untuk membantu freelancer, agency, dan digital marketer mencari calon client secara otomatis menggunakan scraping bisnis dan AI-generated outreach.

Fokus utama project:
- scraping bisnis
- mengambil email / nomor WhatsApp
- generate pesan outreach personal menggunakan AI
- mengirim outreach email
- tracking status outreach sederhana

Project ini dibuat dengan pendekatan MVP:
simple, cepat selesai, dan langsung usable.

---

# Goals

## MVP Goals (100% COMPLETE & DEPLOYED)
- [x] User dapat mencari bisnis berdasarkan niche dan lokasi (Google Maps scraping aktif)
- [x] System dapat mengambil data bisnis & merayapi email otomatis dari website
- [x] System dapat generate outreach message menggunakan AI (Email & WhatsApp khusus Indonesia)
- [x] User dapat mengirim outreach email secara real (SMTP & Gmail compatible)
- [x] User dapat melihat history outreach & update status konversi (Replied, Sent, Failed)
- [x] User dapat melakukan WhatsApp Click-to-Chat outreach terintegrasi
- [x] User dapat melakukan Bulk Checkbox Select & Generate dari tabel Leads langsung
- [x] Auto-Select Leads via AI (Smart Matching) & manual Checkboxes ketika membuat Campaign baru
- [x] Auto-Generate Outreach khusus untuk menjual Jasa Pembuatan Website (Web Design Services)

## Non Goals (belum dibuat)
- microservices
- RabbitMQ
- realtime websocket
- AI multi-agent complex
- LinkedIn automation
- Instagram automation
- multi tenant SaaS
- AI memory
- RAG/vector database

---

# Tech Stack

## Backend
- Laravel 11
- PHP 8.3

## Frontend
- Blade + TailwindCSS

## Database
- MySQL

## AI
- GroqAI API

## Email
- Gmail SMTP 
## Scraping
- Apify / Google Maps scraping API

---

# Core Features

## 1. Lead Scraping
User dapat mencari bisnis berdasarkan:
- niche
- keyword
- lokasi

Contoh:
- klinik gigi surabaya
- cafe bali
- digital agency jakarta

Data yang diambil:
- nama bisnis
- alamat
- website
- nomor telepon
- email
- rating
- kategori bisnis

---

## 2. AI Outreach Generator
System menggunakan AI untuk membuat pesan outreach personal berdasarkan data bisnis.

Contoh output:
- email outreach
- WhatsApp outreach

AI harus menghasilkan:
- pendek
- natural
- tidak terlalu salesy
- personalized

---

## 3. Outreach Sender
User dapat:
- memilih leads
- generate pesan
- mengirim email

Status:
- pending
- sent
- failed
- replied

---

## 4. Dashboard
Menampilkan:
- total leads
- total outreach
- sent rate
- reply rate sederhana

---

# Database Structure

## leads
- id
- business_name
- niche
- website
- email
- phone
- address
- city
- source
- created_at

## campaigns
- id
- name
- niche
- location
- created_at

## outreach_messages
- id
- lead_id
- campaign_id
- subject
- message
- status
- sent_at
- created_at

---

# Folder Structure

```txt
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Services/
│
├── Models/
├── Jobs/
├── Mail/
└── Helpers/

resources/
├── views/
└── js/

routes/
├── web.php
└── api.php
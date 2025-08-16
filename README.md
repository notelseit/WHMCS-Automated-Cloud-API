# Hetzner Cloud VPS Module for WHMCS - Auto-Configured

Un modulo WHMCS avanzato per la gestione automatizzata dei VPS Hetzner Cloud con configurazione dinamica tramite API.

## Caratteristiche Principali

- **Configurazione Automatica**: Le opzioni di configurazione vengono populate automaticamente dall'API Hetzner
- **Cache Intelligente**: Sistema di cache per ridurre le chiamate API e migliorare le performance
- **Aggiornamento Automatico**: Script di aggiornamento automatico per mantenere sincronizzati i dati
- **Gestione Completa**: Creazione, sospensione, riattivazione e terminazione dei server
- **Fallback Resiliente**: Dati di fallback in caso di indisponibilità dell'API
- **Logging Completo**: Sistema di log per monitoraggio e debugging

## Requisiti di Sistema

- WHMCS 7.0 o superiore
- PHP 7.4 o superiore
- Estensioni PHP: cURL, JSON, OpenSSL
- Accesso di scrittura alle directory del modulo
- Token API Hetzner Cloud valido

## Installazione

1. **Carica i file del modulo**
   ```bash
   /path/to/whmcs/modules/servers/hetznervps/
   ├── hetznervps.php              # File principale del modulo
   ├── setup.php                   # Helper di installazione
   ├── scripts/
   │   ├── update_cache.php        # Script PHP per aggiornamento cache
   │   └── update_cache.sh         # Script Bash per cron job
   ├── cache/                      # Directory cache (creata automaticamente)
   ├── logs/                       # Directory log (creata automaticamente)
   └── data/                       # Directory dati server (creata automaticamente)
   ```

2. **Imposta i permessi**
   ```bash
   chmod 755 /path/to/whmcs/modules/servers/hetznervps/
   chmod 755 /path/to/whmcs/modules/servers/hetznervps/scripts/update_cache.sh
   chmod 644 /path/to/whmcs/modules/servers/hetznervps/*.php
   ```

3. **Configura il cron job per aggiornamento automatico**
   ```bash
   # Aggiorna la cache ogni 6 ore
   0 */6 * * * /path/to/whmcs/modules/servers/hetznervps/scripts/update_cache.sh
   ```

## Configurazione WHMCS

1. **Crea un nuovo prodotto** in WHMCS
2. **Seleziona il modulo** "Hetzner Cloud VPS (Auto-Configured)"
3. **Configura i parametri del modulo**:
   - **API Token**: Il tuo token API Hetzner Cloud
   - **Default Location**: Location predefinita (es. fsn1)
   - **Enable Backups**: Abilita backup automatici
   - **Enable Monitoring**: Abilita monitoraggio

## Configurazione delle Opzioni

Il modulo genera automaticamente le seguenti opzioni configurabili:

### Opzioni Dinamiche (Auto-popolate dall'API)
- **Server Configuration**: Dropdown con tutti i tipi di server disponibili
- **Server Location**: Dropdown con tutte le location Hetzner
- **Operating System**: Dropdown con le immagini OS disponibili

### Opzioni Statiche
- **API Token**: Token di accesso all'API Hetzner
- **Default Location**: Location predefinita se non specificata
- **Enable Backups**: Abilita backup automatici
- **Enable Monitoring**: Abilita monitoraggio server

## Funzionalità del Modulo

### Gestione Server
- **Creazione automatica** dei server con configurazioni personalizzate
- **Sospensione/Riattivazione** tramite power off/on
- **Terminazione** con eliminazione completa del server
- **Reset password** con generazione automatica nuova password

### Pulsanti Personalizzati
- **Client Area**: Reboot, Reset Password, Console
- **Admin Area**: View Details, Reboot, Reset Password, Update Cache

### Sistema di Cache
- **Durata cache**: 24 ore per server types, locations e images
- **Aggiornamento automatico**: Tramite cron job ogni 6 ore
- **Fallback intelligente**: Dati predefiniti se API non disponibile

## File del Progetto

### hetznervps.php
File principale del modulo che contiene tutte le funzioni WHMCS standard e le funzionalità avanzate:
- Configurazione dinamica delle opzioni
- Gestione delle chiamate API
- Sistema di cache
- Funzioni di gestione server

### scripts/update_cache.php  
Script PHP per l'aggiornamento automatico della cache:
- Connessione al database WHMCS per ottenere il token API
- Aggiornamento di server types, locations e images
- Gestione errori e logging

### scripts/update_cache.sh
Script Bash per l'esecuzione tramite cron job:
- Controllo disponibilità PHP
- Esecuzione script di aggiornamento
- Pulizia automatica dei log vecchi

### setup.php
Helper per l'installazione e configurazione:
- Controllo requisiti di sistema
- Creazione directory necessarie
- Validazione configurazione
- Template di configurazione

## API Endpoints Utilizzati

Il modulo utilizza i seguenti endpoint dell'API Hetzner Cloud:

- `GET /server_types` - Lista dei tipi di server
- `GET /locations` - Lista delle location
- `GET /images` - Lista delle immagini OS
- `POST /servers` - Creazione nuovo server
- `POST /servers/{id}/actions/poweron` - Accensione server
- `POST /servers/{id}/actions/poweroff` - Spegnimento server
- `POST /servers/{id}/actions/reboot` - Riavvio server
- `POST /servers/{id}/actions/reset_password` - Reset password
- `DELETE /servers/{id}` - Eliminazione server

## Gestione Errori

Il modulo implementa una gestione robusta degli errori:
- **Fallback dati**: Configurazioni predefinite se API non disponibile
- **Retry logic**: Tentativo di recupero da cache in caso di errore API
- **Logging esteso**: Registrazione di tutte le operazioni per debugging
- **Validazione input**: Controllo parametri prima delle chiamate API

## Sicurezza

- **Token encryption**: I token API vengono gestiti in modo sicuro
- **Directory protection**: Cache e data directory protette
- **Input validation**: Validazione di tutti gli input utente
- **Error sanitization**: Gli errori vengono sanitizzati prima della visualizzazione

## Monitoraggio e Manutenzione

### Log Files
- `logs/hetzner_api.log` - Log delle chiamate API
- `logs/cache_update.log` - Log degli aggiornamenti cache

### Cache Files  
- `cache/hetzner_server_types.cache` - Cache tipi server
- `cache/hetzner_locations.cache` - Cache locations
- `cache/hetzner_images.cache` - Cache immagini OS

### Data Files
- `data/server_{service_id}.json` - Dati specifici per ogni server

## Supporto e Contributi

Per supporto, bug report o richieste di funzionalità:
1. Apri una issue su GitHub
2. Fornisci log dettagliati per problemi tecnici
3. Includi versione WHMCS e PHP utilizzate

## Licenza

Questo modulo è distribuito sotto licenza MIT. Vedi il file LICENSE per i dettagli completi.

## Changelog

### v2.0.0
- Implementazione configurazione automatica tramite API
- Sistema di cache avanzato
- Script di aggiornamento automatico
- Gestione errori migliorata
- Logging completo

### v1.0.0
- Versione iniziale con configurazione manuale
# Real-Time Dashboard Implementation

## Overview
The Code & Conquest platform now features a **real-time leaderboard dashboard** that automatically updates when players complete missions, level up, or earn gold. No more manual page refreshes!

## How It Works

### 🔄 **Server-Sent Events (SSE)**
- Uses native browser EventSource API for real-time communication
- Lightweight alternative to WebSockets for one-way server → client updates
- Automatic reconnection when connection is lost

### ⚡ **Smart Updates**
- Only sends updates when leaderboard data actually changes
- Efficient hash-based change detection prevents unnecessary traffic
- Updates every 2 seconds but only transmits when needed

### 🎨 **Visual Feedback**
- **Connection Status**: Green pulsing indicator shows live connection
- **Update Animations**: Highlighted entries when positions change
- **Character Classes**: Color-coded names (Netrunners, Data Miners, Sleuths)
- **Timestamp Display**: Shows when data was last updated

## API Endpoints

### `/dashboard/stream` (SSE Endpoint)
**Purpose**: Server-Sent Events stream for real-time updates
**Format**: 
```
event: leaderboard-update
data: {"byReputation": [...], "byWealth": [...], "byEfficiency": [...], "timestamp": 1762135941}
```

### `/api/dashboard/data` (REST API)
**Purpose**: One-time leaderboard data fetch
**Response**: JSON with current leaderboard standings

## Event Triggers

The dashboard automatically updates when:
- ✅ **Mission Completed**: Character earnings and efficiency rankings change
- ✅ **Level Up**: Reputation leaderboard updates
- ✅ **Gold Changes**: Wealth rankings adjust

## Technical Features

### **Client-Side (Stimulus Controller)**
- Handles EventSource connection management
- Smooth animations for leaderboard changes
- Automatic reconnection with exponential backoff
- Clean disconnection when user navigates away

### **Server-Side (Symfony Controller)**
- Efficient data querying with hash-based change detection
- Graceful handling of client disconnections
- Memory-efficient streaming implementation

### **CSS Animations**
- Highlight effect for updated entries
- Slide-in animation for new entries
- Pulsing connection indicator
- Character class color coding

## Usage

Simply visit the dashboard at `/` and watch the leaderboards update in real-time as players engage with the platform. The system handles all connection management automatically.

## Performance Considerations

- **2-second polling interval** balances responsiveness with server load
- **Hash-based change detection** prevents unnecessary data transmission
- **Efficient queries** with proper indexing for fast leaderboard generation
- **Automatic cleanup** when clients disconnect

The real-time dashboard transforms the static leaderboard into a dynamic, engaging experience that keeps players connected to the competitive aspects of the platform! 🚀
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["reputationList", "wealthList", "efficiencyList", "lastUpdate"]
    
    connect() {
        console.log("Real-time dashboard connected")
        this.connected = true
        this.setupEventSource()
        this.updateTimestamp()
        
        // Update timestamp every second
        this.timestampInterval = setInterval(() => {
            this.updateTimestamp()
        }, 1000)
        
        // Update connection status
        this.updateConnectionStatus(true)
    }
    
    disconnect() {
        this.connected = false
        if (this.eventSource) {
            this.eventSource.close()
        }
        if (this.timestampInterval) {
            clearInterval(this.timestampInterval)
        }
        this.updateConnectionStatus(false)
    }
    
    setupEventSource() {
        if (!this.connected) return
        
        this.eventSource = new EventSource('/dashboard/stream')
        
        this.eventSource.addEventListener('open', () => {
            console.log('EventSource connected')
            this.updateConnectionStatus(true)
        })
        
        this.eventSource.addEventListener('leaderboard-update', (event) => {
            const data = JSON.parse(event.data)
            this.updateLeaderboards(data)
            this.lastUpdateTime = data.timestamp
            this.updateConnectionStatus(true)
        })
        
        this.eventSource.addEventListener('error', (event) => {
            console.error('EventSource failed:', event)
            this.updateConnectionStatus(false)
            
            // Close the current connection
            if (this.eventSource) {
                this.eventSource.close()
            }
            
            // Attempt to reconnect after 5 seconds if still connected to DOM
            if (this.connected) {
                setTimeout(() => {
                    if (this.connected) {
                        this.setupEventSource()
                    }
                }, 5000)
            }
        })
    }
    
    updateConnectionStatus(isConnected) {
        const statusIndicator = this.element.querySelector('.status-indicator')
        const statusText = this.element.querySelector('.realtime-status')
        
        if (statusIndicator && statusText) {
            if (isConnected) {
                statusIndicator.classList.remove('disconnected')
                statusText.classList.remove('disconnected')
            } else {
                statusIndicator.classList.add('disconnected')
                statusText.classList.add('disconnected')
            }
        }
    }
    
    updateLeaderboards(data) {
        // Add visual feedback for updates
        this.element.classList.add('updating')
        setTimeout(() => {
            this.element.classList.remove('updating')
        }, 500)
        
        // Update each leaderboard
        this.updateLeaderboard(this.reputationListTarget, data.byReputation, 'level', 'LVL')
        this.updateLeaderboard(this.wealthListTarget, data.byWealth, 'gold', 'Credits')
        this.updateLeaderboard(this.efficiencyListTarget, data.byEfficiency, 'missions', 'Missions')
    }
    
    updateLeaderboard(listElement, characters, scoreField, scoreSuffix) {
        const currentItems = Array.from(listElement.children)
        
        characters.forEach((character, index) => {
            const rank = index + 1
            const existingItem = currentItems[index]
            
            if (existingItem) {
                // Update existing item
                const nameSpan = existingItem.querySelector('.name')
                const scoreSpan = existingItem.querySelector('.score')
                const rankSpan = existingItem.querySelector('.rank')
                
                if (nameSpan.textContent !== character.name) {
                    nameSpan.textContent = character.name
                    existingItem.classList.add('updated')
                    setTimeout(() => existingItem.classList.remove('updated'), 1000)
                }
                
                const newScore = `${character[scoreField]} ${scoreSuffix}`
                if (scoreSpan.textContent !== newScore) {
                    scoreSpan.textContent = newScore
                    existingItem.classList.add('updated')
                    setTimeout(() => existingItem.classList.remove('updated'), 1000)
                }
                
                rankSpan.textContent = `#${rank}`
                
                // Add character class for styling
                existingItem.className = `character-${character.characterClass}`
            } else {
                // Create new item
                const newItem = this.createLeaderboardItem(rank, character, scoreField, scoreSuffix)
                listElement.appendChild(newItem)
            }
        })
        
        // Remove extra items if there are fewer characters now
        while (listElement.children.length > characters.length) {
            listElement.removeChild(listElement.lastChild)
        }
    }
    
    createLeaderboardItem(rank, character, scoreField, scoreSuffix) {
        const li = document.createElement('li')
        li.className = `character-${character.characterClass} new-entry`
        
        li.innerHTML = `
            <span class="rank">#${rank}</span>
            <span class="name">${character.name}</span>
            <span class="score">${character[scoreField]} ${scoreSuffix}</span>
        `
        
        // Remove new-entry class after animation
        setTimeout(() => li.classList.remove('new-entry'), 1000)
        
        return li
    }
    
    updateTimestamp() {
        if (this.lastUpdateTime && this.hasLastUpdateTarget) {
            const now = Math.floor(Date.now() / 1000)
            const secondsAgo = now - this.lastUpdateTime
            
            let timeText
            if (secondsAgo < 60) {
                timeText = `${secondsAgo}s ago`
            } else if (secondsAgo < 3600) {
                timeText = `${Math.floor(secondsAgo / 60)}m ago`
            } else {
                timeText = `${Math.floor(secondsAgo / 3600)}h ago`
            }
            
            this.lastUpdateTarget.textContent = `Last updated: ${timeText}`
        }
    }
}
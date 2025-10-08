// HomeAid Booking System JavaScript

// Provider Selection Modal Functions
function openProviderModal(serviceId, serviceName) {
    document.getElementById('selectedServiceId').value = serviceId;
    document.getElementById('modalServiceName').textContent = serviceName;
    
    // Show loading state
    const providerList = document.getElementById('providerList');
    providerList.innerHTML = '<div class="text-center"><div class="loading"></div><p>Loading providers...</p></div>';
    
    // Show modal
    document.getElementById('providerModal').style.display = 'block';
    
    // Fetch providers for this service
    fetchProviders(serviceId);
}

function closeProviderModal() {
    document.getElementById('providerModal').style.display = 'none';
    document.getElementById('selectedProviderId').value = '';
    
    // Clear selections
    const providerItems = document.querySelectorAll('.provider-item');
    providerItems.forEach(item => item.classList.remove('selected'));
}

function fetchProviders(serviceId) {
    const lat = document.getElementById('userLat') ? document.getElementById('userLat').value : '';
    const lng = document.getElementById('userLng') ? document.getElementById('userLng').value : '';
    const radiusEl = document.getElementById('radiusSelect');
    const radius = radiusEl ? radiusEl.value : 25;
    const hasLocation = lat && lng;
    const baseUrl = hasLocation ? `../api/search_providers.php?service_id=${serviceId}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&radius=${radius}` : `../api/get_providers.php?service_id=${serviceId}`;
    fetch(baseUrl)
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.providers) {
                    // If using location but none found, fallback to non-location list
                    if (data.location_used && Array.isArray(data.providers) && data.providers.length === 0) {
                        const fallbackUrl = `../api/get_providers.php?service_id=${serviceId}`;
                        fetch(fallbackUrl)
                            .then(fr => fr.json())
                            .then(fallback => {
                                const note = `No providers within ${data.radius_km || radius} km. Showing all providers.`;
                                displayProviders(fallback, false, note);
                            })
                            .catch(err => {
                                document.getElementById('providerList').innerHTML = `<div class="empty-state"><p>Error loading providers: ${err.message}</p></div>`;
                            });
                    } else {
                        displayProviders(data.providers, data.location_used);
                    }
                } else {
                    displayProviders(data);
                }
            } catch (e) {
                document.getElementById('providerList').innerHTML = `<div class="empty-state"><p>Error parsing provider data: ${e.message}</p></div>`;
            }
        })
        .catch(err => {
            document.getElementById('providerList').innerHTML = `<div class="empty-state"><p>Error loading providers: ${err.message}</p></div>`;
        });
}

function displayProviders(providers, locationUsed=false, noteMessage) {
    const providerList = document.getElementById('providerList');
    if (!providerList) return;

    // Error handling
    if (!Array.isArray(providers)) {
        let errorMsg = providers && providers.error ? providers.error : 'Unknown error.';
        providerList.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><h3>Error loading providers</h3><p>${errorMsg}</p></div>`;
        return;
    }
    if (providers.length === 0) {
        providerList.innerHTML = `<div class="empty-state"><div class="empty-icon">👥</div><h3>No Providers Available</h3><p>There are no providers available for this service right now.</p></div>`;
        return;
    }

    const cards = providers.map(p => {
        const distanceLine = (locationUsed && p.distance!=null) ? `<div class="prov-distance">${p.distance} km away</div>` : '';
        const phoneLine = p.phone ? `<div class="prov-phone">📞 ${p.phone}</div>` : '';
        const img = p.photo ? `<img src="../assets/uploads/${p.photo}" alt="${p.full_name}" class="prov-avatar" loading="lazy">` : `<div class="prov-avatar placeholder">👤</div>`;
        return `<div class="provider-card" data-provider-id="${p.user_id}" onclick="selectProvider(${p.user_id}, '${p.full_name.replace(/'/g, "&#39;")}', ${p.rate})">
            <div class="prov-media">${img}</div>
            <div class="prov-body">
                <div class="prov-header">
                    <h4 class="prov-name">${p.full_name}</h4>
                    <div class="prov-rate">₹${p.rate}/hr</div>
                </div>
                ${distanceLine}
                ${phoneLine}
            </div>
        </div>`;
    }).join('');
    providerList.classList.add('provider-card-grid');
    providerList.innerHTML = (noteMessage ? `<div class="notification notification-info" style="margin-bottom:8px;">${noteMessage}</div>` : '') + cards;
}

function selectProvider(providerId, providerName, rate) {
    // Clear previous selections across legacy & new classes
    document.querySelectorAll('.provider-item, .provider-card').forEach(el=>el.classList.remove('selected'));
    // Try to find the element by data-provider-id
    const card = document.querySelector(`.provider-card[data-provider-id='${providerId}']`);
    if(card) card.classList.add('selected');
    // Hidden form values
    const idEl = document.getElementById('selectedProviderId');
    if(idEl){ idEl.value = providerId; }
    const nameEl = document.getElementById('selectedProviderName');
    if(nameEl){ nameEl.value = providerName; }
    const rateEl = document.getElementById('selectedProviderRate');
    if(rateEl){ rateEl.value = rate; }
    const bookNowBtn = document.getElementById('bookNowBtn');
    if(bookNowBtn){ bookNowBtn.disabled = false; bookNowBtn.textContent = `Book ${providerName} - ₹${rate}/hour`; }
}

function bookNow() {
    const serviceId = document.getElementById('selectedServiceId').value;
    const providerId = document.getElementById('selectedProviderId').value;
    
    if (!serviceId || !providerId) {
        alert('Please select a provider first.');
        return;
    }
    
    // Submit booking form
    document.getElementById('bookingForm').submit();
}

// Add to Cart Functions
function addToCart(serviceId, serviceName) {
    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('action', 'add');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${serviceName} added to cart!`, 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Failed to add to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('Error adding to cart', 'error');
    });
}

function updateCartCount() {
    fetch('cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                cartBadge.textContent = data.count;
                cartBadge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Notification System
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;font-size:1.2rem;cursor:pointer;margin-left:1rem;">&times;</button>
    `;
    
    // Add to page
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;';
        document.body.appendChild(container);
    }
    
    container.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Search and Filter Functions
function filterServices() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const priceSort = document.getElementById('priceSort').value;
    
    const serviceCards = document.querySelectorAll('.service-card');
    let visibleCards = [];
    
    serviceCards.forEach(card => {
        const serviceName = card.querySelector('h3').textContent.toLowerCase();
        const serviceCategory = card.dataset.category || '';
        
        let show = true;
        
        // Text search filter
        if (searchTerm && !serviceName.includes(searchTerm)) {
            show = false;
        }
        
        // Category filter
        if (categoryFilter && serviceCategory !== categoryFilter) {
            show = false;
        }
        
        if (show) {
            card.style.display = 'block';
            visibleCards.push(card);
        } else {
            card.style.display = 'none';
        }
    });
    
    // Sort by price if selected
    if (priceSort && visibleCards.length > 0) {
        const container = visibleCards[0].parentElement;
        
        visibleCards.sort((a, b) => {
            const priceA = parseFloat(a.dataset.minPrice || 0);
            const priceB = parseFloat(b.dataset.minPrice || 0);
            
            return priceSort === 'asc' ? priceA - priceB : priceB - priceA;
        });
        
        visibleCards.forEach(card => container.appendChild(card));
    }
    
    // Show empty state if no results
    const emptyState = document.getElementById('emptyState');
    if (visibleCards.length === 0) {
        if (!emptyState) {
            const empty = document.createElement('div');
            empty.id = 'emptyState';
            empty.className = 'empty-state';
            empty.innerHTML = `
                <div class="empty-icon">🔍</div>
                <h3>No Services Found</h3>
                <p>Try adjusting your search or filter criteria.</p>
            `;
            document.querySelector('.service-grid').appendChild(empty);
        }
    } else if (emptyState) {
        emptyState.remove();
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count on page load
    updateCartCount();
    
    // Add event listeners for search and filter
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterServices);
    }
    
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterServices);
    }
    
    const priceSort = document.getElementById('priceSort');
    if (priceSort) {
        priceSort.addEventListener('change', filterServices);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('providerModal');
        if (event.target === modal) {
            closeProviderModal();
        }
    });
});

// CSS for notifications (injected via JavaScript)
const notificationStyles = `
.notification {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
}

.notification-success {
    border-left-color: #059669;
    color: #065f46;
}

.notification-error {
    border-left-color: #dc2626;
    color: #991b1b;
}

.notification-info {
    border-left-color: #2563eb;
    color: #1e40af;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
`;

// Inject notification styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

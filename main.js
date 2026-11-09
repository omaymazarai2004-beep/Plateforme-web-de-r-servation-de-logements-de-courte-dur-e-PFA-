// assets/js/main.js

// Dropdown
function toggleDropdown() {
    document.getElementById('dropdown').classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-profile') && !e.target.closest('.dropdown')) {
        document.getElementById('dropdown').classList.remove('show');
    }
});

// Modals
function showLogin() {
    document.getElementById('loginModal').classList.add('show');
    document.getElementById('dropdown').classList.remove('show');
}

function showRegister() {
    document.getElementById('registerModal').classList.add('show');
    document.getElementById('loginModal').classList.remove('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Search
function searchLogements() {
    const destination = document.getElementById('searchDestination').value;
    const dateStart = document.getElementById('searchDateStart').value;
    const dateEnd = document.getElementById('searchDateEnd').value;
    
    let url = 'index.php?';
    if (destination) url += 'ville=' + encodeURIComponent(destination) + '&';
    if (dateStart) url += 'date_deb=' + dateStart + '&';
    if (dateEnd) url += 'date_fin=' + dateEnd;
    
    window.location.href = url;
}

// Filter categories
function filterCat(element, type) {
    document.querySelectorAll('.cat-item').forEach(c => c.classList.remove('active'));
    element.classList.add('active');
    
    if (type === 'all') {
        window.location.href = 'index.php';
    } else {
        window.location.href = 'index.php?type=' + type;
    }
}

// Toggle favori
function toggleFavori(btn, idLogement) {
    fetch('api/toggle_favori.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_logement=' + idLogement
    })
    .then(response => response.json())
    .then(data => {
        const icon = btn.querySelector('i');
        if (data.favori) {
            icon.className = 'fa-solid fa-heart';
            icon.style.color = 'var(--rose)';
            btn.classList.add('active');
        } else {
            icon.className = 'fa-regular fa-heart';
            icon.style.color = 'var(--dark)';
            btn.classList.remove('active');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Load more listings (infinite scroll)
let isLoading = false;
let currentPage = 1;

window.addEventListener('scroll', function() {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadMoreListings();
    }
});

function loadMoreListings() {
    if (isLoading) return;
    isLoading = true;
    
    currentPage++;
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type') || '';
    const ville = urlParams.get('ville') || '';
    
    fetch(`api/get_logements.php?page=${currentPage}&type=${type}&ville=${ville}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const grid = document.getElementById('listingsGrid');
                data.forEach(logement => {
                    grid.insertAdjacentHTML('beforeend', createListingCard(logement));
                });
            }
            isLoading = false;
        })
        .catch(error => {
            console.error('Error:', error);
            isLoading = false;
        });
}

function createListingCard(logement) {
    return `
        <div class="card" onclick="window.location.href='logement-detail.php?id=${logement.id_logement}'">
            <div class="card-img">
                <img src="${logement.image_url || 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80'}" alt="${logement.titre}"/>
                <button class="card-fav" onclick="event.stopPropagation(); toggleFavori(this, ${logement.id_logement})">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
            <div class="card-info">
                <div class="card-title">${logement.titre}</div>
                <div class="card-sub">${logement.region_ville}</div>
                <div class="card-price"><strong>${logement.prix_nuit} TND</strong> <span>/ nuit</span></div>
            </div>
        </div>
    `;
}

// Notification de succès/erreur
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Style pour les notifications
const style = document.createElement('style');
style.textContent = `
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
document.head.appendChild(style);
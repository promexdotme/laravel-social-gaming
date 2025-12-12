$(document).ready(function () {
    // Search functionality
    let searchTimeout;

    $('#game-search').on('keyup', function () {
        clearTimeout(searchTimeout);
        const query = $(this).val();

        searchTimeout = setTimeout(function () {
            $('.game-card').each(function () {
                const title = $(this).data('title').toLowerCase();
                if (title.includes(query.toLowerCase())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }, 300);
    });

    // Modal Functionality
    $('.open-modal').on('click', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        $('#' + target).addClass('show');

        if (target === 'modal-profile') {
            loadProfileData();
        }
    });

    $('.close-modal').on('click', function () {
        $(this).closest('.modal').removeClass('show');
    });

    $(window).on('click', function (e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').removeClass('show');
        }
    });

    // Login Form Submission
    $('#login-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const messageDiv = form.find('.form-message');

        $.ajax({
            url: '/login',
            method: 'POST',
            data: form.serialize() + '&is_ajax=1',
            success: function (response) {
                location.reload();
            },
            error: function (xhr) {
                let errorMsg = 'Login failed';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON[0]) {
                    errorMsg = xhr.responseJSON[0];
                }
                messageDiv.text(errorMsg).show();
            }
        });
    });

    // Register Form Submission
    $('#register-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const messageDiv = form.find('.form-message');

        $.ajax({
            url: '/register',
            method: 'POST',
            data: form.serialize() + '&is_ajax=1',
            success: function (response) {
                location.reload();
            },
            error: function (xhr) {
                let errorMsg = 'Registration failed';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON[0]) {
                    errorMsg = xhr.responseJSON[0];
                }
                messageDiv.text(errorMsg).show();
            }
        });
    });

    // Load Profile Data
    function loadProfileData() {
        const container = $('#profile-data');

        $.ajax({
            url: '/profile/ajax',
            method: 'GET',
            success: function (response) {
                if (response.profile) {
                    let html = `
                        <div class="profile-info">
                            <p><strong>Balance:</strong> ${response.profile.balance} ${response.currency}</p>
                            <p><strong>Rating:</strong> ${response.profile.rating}</p>
                        </div>
                    `;

                    if (response.profile.bonus && response.profile.bonus.available) {
                        html += `
                            <div class="bonus-info">
                                <h3>Bonuses</h3>
                                <p><strong>Total Bonus:</strong> ${response.profile.bonus.balance}</p>
                                <div class="bonus-details">${response.profile.bonus.tooltip}</div>
                            </div>
                        `;
                    }

                    container.html(html);
                }
            },
            error: function () {
                container.html('<p class="error">Failed to load profile data.</p>');
            }
        });
    }
});

/**
 * Manager Panel JavaScript
 * Панель управления участниками для менеджеров
 */

(function() {
    'use strict';

    /**
     * Изменение статуса участника
     */
    window.changeMemberStatus = function(memberId, status) {
        const statusLabels = {
            'publish': 'одобрить',
            'pending': 'отправить на модерацию',
            'draft': 'перевести в черновики'
        };

        if (!confirm(`Вы уверены, что хотите ${statusLabels[status]} этого участника?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'manager_change_member_status');
        formData.append('nonce', managerPanelData.nonce);
        formData.append('member_id', memberId);
        formData.append('status', status);

        fetch(managerPanelData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.message);
                location.reload();
            } else {
                alert(data.data.message || 'Произошла ошибка');
            }
        })
        .catch(error => {
            alert('Произошла ошибка при изменении статуса');
        });
    };

    /**
     * Удаление участника
     */
    window.deleteMember = function(memberId) {
        if (!confirm('Вы уверены, что хотите удалить этого участника? Это действие необратимо.')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'manager_delete_member');
        formData.append('nonce', managerPanelData.nonce);
        formData.append('member_id', memberId);

        fetch(managerPanelData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.message);
                location.reload();
            } else {
                alert(data.data.message || 'Произошла ошибка');
            }
        })
        .catch(error => {
            alert('Произошла ошибка при удалении участника');
        });
    };

})();

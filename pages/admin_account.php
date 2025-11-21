<?php

// ---------- Pending Accounts ----------
$pendingSql = "
    SELECT a.account_id,
           a.account_number,
           a.account_type,
           a.balance,
           a.status,
           a.min_balance,
           a.ifsc_code,
           a.account_date,
           p.full_name,
           p.phone,
           u.username,
           u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.status = 'Pending'
";
$pendingRes = mysqli_query($conn, $pendingSql);

// ---------- All Accounts ----------
$accountsSql = "
    SELECT a.account_id,
           a.account_number,
           a.account_type,
           a.balance,
           a.status,
           a.ifsc_code,
           a.account_date,
           p.full_name,
           p.phone,
           u.username,
           u.email
    FROM account a
    JOIN profile p ON a.profile_id = p.id
    JOIN users u ON p.user_id = u.id
    ORDER BY a.account_id DESC
";
$accountsRes = mysqli_query($conn, $accountsSql);
?>

<h4 class="section-title">Account Management</h4>

<!-- Tabs -->
<div class="section-card" style="margin-bottom: 16px;">
    <div class="tab-buttons" style="display:flex; gap:8px; margin-bottom:8px;">
        <button type="button"
            class="btn-link btn-approve"
            onclick="showAdminTab('pending')"
            id="btn-tab-pending">
            Pending Requests
        </button>
        <button type="button"
            class="btn-link"
            onclick="showAdminTab('all')"
            id="btn-tab-all">
            All Accounts
        </button>
    </div>

    <p class="helper-text" style="font-size: 13px; opacity: 0.8;">
    </p>
</div>

<!-- PENDING TAB -->
<div id="admin-tab-pending" class="section-card">

    <?php if ($pendingRes && mysqli_num_rows($pendingRes) > 0): ?>

        <div class="table-wrapper">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Login Username</th>
                        <th>Email</th>
                        <th>Account No</th>
                        <th>Type</th>
                        <th>IFSC Code</th>
                        <th>Account Date</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th style="width:180px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($pendingRes)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['account_type']) ?></td>
                        <td><?= htmlspecialchars($row['ifsc_code']) ?></td>
                        <td><?= htmlspecialchars($row['account_date']) ?></td>
                        <td><?= number_format($row['balance'], 2) ?></td>

                        <td>
                            <span class="status-badge status-pending">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>

                        <td>
                            <a href="../actions/approve_account.php?id=<?= (int)$row['account_id'] ?>"
                               class="btn-link btn-approve"
                               onclick="return confirm('Approve this account?');">
                                Approve
                            </a>

                            <a href="../actions/decline_account.php?id=<?= (int)$row['account_id'] ?>"
                               class="btn-link btn-decline"
                               onclick="return confirm('Are you sure you want to DECLINE this account request?');">
                                Decline
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p class="empty-text">No pending account requests.</p>
    <?php endif; ?>

</div>

<!-- ALL ACCOUNTS TAB -->
<div id="admin-tab-all" class="section-card" style="display:none;">

    <?php if ($accountsRes && mysqli_num_rows($accountsRes) > 0): ?>

        <div class="table-wrapper">
            <table class="ui-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Login Username</th>
                        <th>Email</th>
                        <th>Account No</th>
                        <th>Type</th>
                        <th>IFSC Code</th>
                        <th>Account Date</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($accountsRes)): ?>
                    <?php
                        $status = htmlspecialchars($row['status']);
                        $statusClass =
                            strtolower($status) === 'active' ? 'status-active' :
                            (strtolower($status) === 'pending' ? 'status-pending' : 'status-other');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['account_type']) ?></td>
                        <td><?= htmlspecialchars($row['ifsc_code']) ?></td>
                        <td><?= htmlspecialchars($row['account_date']) ?></td>
                        <td><?= number_format($row['balance'], 2) ?></td>

                        <td>
                            <span class="status-badge <?= $statusClass ?>">
                                <?= $status ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p class="empty-text">No accounts found.</p>
    <?php endif; ?>

</div>

<script>
function showAdminTab(tab) {
    const pending = document.getElementById('admin-tab-pending');
    const allTab  = document.getElementById('admin-tab-all');
    const btnPending = document.getElementById('btn-tab-pending');
    const btnAll     = document.getElementById('btn-tab-all');

    if (tab === 'pending') {
        pending.style.display = 'block';
        allTab.style.display  = 'none';
        btnPending.classList.add('btn-approve');
        btnAll.classList.remove('btn-approve');
    } else {
        pending.style.display = 'none';
        allTab.style.display  = 'block';
        btnAll.classList.add('btn-approve');
        btnPending.classList.remove('btn-approve');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    showAdminTab('pending');
});
</script>

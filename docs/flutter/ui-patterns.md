# UI Patterns & Components — Kojayaku Flutter App

**Versi:** 1.0.0
**Purpose:** Reusable UI patterns, widgets, and design tokens for consistent implementation.

---

## 1. Design Tokens

### Color Palette

```dart
class AppColors {
  // Primary
  static const primary = Color(0xFF1E40AF);       // Blue 800
  static const primaryLight = Color(0xFF3B82F6);  // Blue 500
  static const primaryDark = Color(0xFF1E3A8A);   // Blue 900

  // Accent
  static const accent = Color(0xFF059669);         // Emerald 600
  static const accentLight = Color(0xFF10B981);    // Emerald 500

  // Status
  static const success = Color(0xFF10B981);         // Green
  static const warning = Color(0xFFF59E0B);         // Amber
  static const danger = Color(0xFFEF4444);           // Red
  static const info = Color(0xFF3B82F6);             // Blue
  static const neutral = Color(0xFF6B7280);          // Gray

  // Background
  static const background = Color(0xFFF9FAFB);       // Gray 50
  static const surface = Color(0xFFFFFFFF);
  static const surfaceVariant = Color(0xFFF3F4F6);   // Gray 100

  // Text
  static const textPrimary = Color(0xFF111827);      // Gray 900
  static const textSecondary = Color(0xFF6B7280);    // Gray 500
  static const textDisabled = Color(0xFF9CA3AF);     // Gray 400
}
```

### Typography

```dart
class AppTextStyles {
  static const headlineLarge = TextStyle(fontSize: 28, fontWeight: FontWeight.w700);
  static const headlineMedium = TextStyle(fontSize: 24, fontWeight: FontWeight.w700);
  static const titleLarge = TextStyle(fontSize: 20, fontWeight: FontWeight.w600);
  static const titleMedium = TextStyle(fontSize: 16, fontWeight: FontWeight.w600);
  static const bodyLarge = TextStyle(fontSize: 16, fontWeight: FontWeight.w400);
  static const bodyMedium = TextStyle(fontSize: 14, fontWeight: FontWeight.w400);
  static const bodySmall = TextStyle(fontSize: 12, fontWeight: FontWeight.w400);
  static const labelLarge = TextStyle(fontSize: 14, fontWeight: FontWeight.w500);
  static const labelSmall = TextStyle(fontSize: 11, fontWeight: FontWeight.w500);
}
```

### Spacing

```dart
class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
  static const xxl = 48.0;
}
```

---

## 2. Reusable Widgets

### 2.1 StatusBadge

```dart
class StatusBadge extends StatelessWidget {
  final String label;
  final String status;

  const StatusBadge({
    required this.label,
    required this.status,
  });

  factory StatusBadge.forMemberStatus(String status) {
    return StatusBadge(
      label: _memberStatusLabel(status),
      status: status,
    );
  }

  static String _memberStatusLabel(String status) => switch (status) {
    'ACTIVE' => 'Aktif',
    'INACTIVE' => 'Nonaktif',
    'PENDING' => 'Menunggu',
    'RESIGNED' => 'Berhenti',
    _ => status,
  };

  Color get _color => switch (status) {
    'ACTIVE' || 'APPROVED' || 'PAID' || 'COMPLETED' || 'FIT' || 'VALID'
      => AppColors.success,
    'PENDING' || 'IN_PROGRESS' || 'PARTIAL' || 'OPEN'
      => AppColors.warning,
    'RESIGNED' || 'REJECTED' || 'REVOKED' || 'EXPIRED' || 'UNFIT' || 'VOIDED'
      => AppColors.danger,
    'INACTIVE' || 'OFF'
      => AppColors.neutral,
    _ => AppColors.info,
  };

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: _color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: AppTextStyles.labelSmall.copyWith(color: _color),
      ),
    );
  }
}
```

### 2.2 SummaryCard

```dart
class SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  const SummaryCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Icon(icon, color: color, size: 20),
                  Icon(Icons.chevron_right, color: AppColors.textDisabled, size: 16),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(value, style: AppTextStyles.titleLarge),
              const SizedBox(height: 2),
              Text(title, style: AppTextStyles.bodySmall.copyWith(color: AppColors.textSecondary)),
            ],
          ),
        ),
      ),
    );
  }
}
```

### 2.3 EmptyState

```dart
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;

  const EmptyState({
    required this.icon,
    required this.title,
    this.subtitle,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 64, color: AppColors.textDisabled),
            const SizedBox(height: AppSpacing.md),
            Text(title, style: AppTextStyles.titleMedium, textAlign: TextAlign.center),
            if (subtitle != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(subtitle!, style: AppTextStyles.bodyMedium.copyWith(color: AppColors.textSecondary), textAlign: TextAlign.center),
            ],
            if (actionLabel != null) ...[
              const SizedBox(height: AppSpacing.lg),
              ElevatedButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}
```

### 2.4 LoadingSkeleton (Shimmer)

```dart
class LoadingSkeleton extends StatelessWidget {
  final double width;
  final double height;
  final BorderRadius borderRadius;

  const LoadingSkeleton({
    this.width = double.infinity,
    this.height = 16,
    this.borderRadius = const BorderRadius.all(Radius.circular(4)),
  });

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppColors.surfaceVariant,
      highlightColor: AppColors.surface,
      child: Container(
        width: width,
        height: height,
        decoration: BoxDecoration(
          color: AppColors.surfaceVariant,
          borderRadius: borderRadius,
        ),
      ),
    );
  }
}

class SummaryCardSkeleton extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const LoadingSkeleton(width: 24, height: 24),
            const SizedBox(height: AppSpacing.sm),
            const LoadingSkeleton(width: 100, height: 24),
            const SizedBox(height: 4),
            const LoadingSkeleton(width: 60, height: 12),
          ],
        ),
      ),
    );
  }
}
```

### 2.5 FormField with Error

```dart
class AppTextFormField extends StatelessWidget {
  final String label;
  final String? hintText;
  final String? errorText;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final bool obscureText;
  final int maxLines;
  final String? Function(String?)? validator;
  final void Function(String)? onChanged;

  const AppTextFormField({
    required this.label,
    this.hintText,
    this.errorText,
    this.controller,
    this.keyboardType,
    this.obscureText = false,
    this.maxLines = 1,
    this.validator,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.labelLarge),
        const SizedBox(height: 4),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          obscureText: obscureText,
          maxLines: maxLines,
          validator: validator,
          onChanged: onChanged,
          decoration: InputDecoration(
            hintText: hintText,
            errorText: errorText,
            border: const OutlineInputBorder(),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          ),
        ),
      ],
    );
  }
}
```

### 2.6 CurrencyDisplay

```dart
class CurrencyDisplay extends StatelessWidget {
  final double amount;
  final TextStyle? style;
  final bool showSign;

  const CurrencyDisplay({
    required this.amount,
    this.style,
    this.showSign = false,
  });

  static String format(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'id_ID',
      symbol: 'Rp ',
      decimalDigits: 0,
    );
    return formatter.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    final prefix = showSign
      ? (amount >= 0 ? '+ ' : '- ')
      : '';
    final display = showSign ? amount.abs() : amount;

    return Text(
      '$prefix${format(display)}',
      style: style ?? AppTextStyles.titleMedium,
    );
  }
}
```

---

## 3. Screen Patterns

### 3.1 List Screen with Search

```dart
class MemberListScreen extends ConsumerStatefulWidget {
  @override
  ConsumerState<MemberListScreen> createState() => _MemberListScreenState();
}

class _MemberListScreenState extends ConsumerState<MemberListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(memberListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Anggota Koperasi')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: SearchBar(
              controller: _searchController,
              hintText: 'Cari nama, email, atau nomor anggota...',
              onChanged: (query) {
                ref.read(memberListProvider.notifier).search(query);
              },
            ),
          ),
          // Status filter chips
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _FilterChip(label: 'Semua', status: null),
                  _FilterChip(label: 'Aktif', status: 'ACTIVE'),
                  _FilterChip(label: 'Menunggu', status: 'PENDING'),
                  _FilterChip(label: 'Nonaktif', status: 'INACTIVE'),
                  _FilterChip(label: 'Berhenti', status: 'RESIGNED'),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          // List
          Expanded(
            child: state.when(
              loading: () => const _LoadingList(),
              error: (e, _) => ErrorWidget(message: e.toString()),
              data: (data) => data.members.isEmpty
                ? EmptyState(
                    icon: Icons.people_outline,
                    title: 'Belum ada anggota',
                    actionLabel: 'Tambah Anggota',
                    onAction: () => context.push('/members/create'),
                  )
                : RefreshIndicator(
                    onRefresh: () => ref.refresh(memberListProvider.future),
                    child: ListView.builder(
                      controller: _scrollController,
                      itemCount: data.members.length + (data.hasMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index >= data.members.length) {
                          ref.read(memberListProvider.notifier).loadMore();
                          return const Padding(
                            padding: EdgeInsets.all(16),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }
                        return _MemberTile(member: data.members[index]);
                      },
                    ),
                  ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/members/create'),
        child: const Icon(Icons.person_add),
      ),
    );
  }
}
```

### 3.2 Detail Screen with Tabs

```dart
class MemberDetailScreen extends ConsumerWidget {
  final int memberId;

  const MemberDetailScreen({required this.memberId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(memberDetailProvider(memberId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detail Anggota'),
        actions: [
          PopupMenuButton(items: [
            const PopupMenuItem(value: 'edit', child: Text('Edit')),
            const PopupMenuItem(value: 'activate', child: Text('Aktifkan')),
            const PopupMenuItem(value: 'resign', child: Text('Berhentikan')),
          ], onSelected: (value) {
            _handleAction(context, ref, value);
          }),
        ],
      ),
      body: detail.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (member) => DefaultTabController(
          length: 4,
          child: Column(
            children: [
              // Header card
              _MemberHeaderCard(member: member),
              // Tab bar
              const TabBar(tabs: [
                Tab(text: 'Info'),
                Tab(text: 'Iuran'),
                Tab(text: 'Bayar'),
                Tab(text: 'Buku Besar'),
              ]),
              // Tab views
              Expanded(
                child: TabBarView(children: [
                  _InfoTab(member: member),
                  _DuesTab(memberId: member.id),
                  _PaymentsTab(memberId: member.id),
                  _LedgerTab(memberId: member.id),
                ]),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

### 3.3 Form Screen

```dart
class PaymentFormScreen extends ConsumerStatefulWidget {
  @override
  ConsumerState<PaymentFormScreen> createState() => _PaymentFormScreenState();
}

class _PaymentFormScreenState extends ConsumerState<PaymentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  CooperativeMemberModel? _selectedMember;
  CooperativeDuesInvoiceModel? _selectedInvoice;
  String _paymentMethod = 'CASH';
  DateTime _paidAt = DateTime.now();

  @override
  Widget build(BuildContext context) {
    final formState = ref.watch(paymentFormProvider);

    ref.listen<PaymentFormState>(paymentFormProvider, (prev, next) {
      next.when(
        initial: () {},
        loading: () {},
        success: (payment) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Pembayaran berhasil disimpan')),
          );
          context.pop();
        },
        validationError: (errors) {
          // Display field errors
        },
        error: (message) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(message), backgroundColor: AppColors.danger),
          );
        },
      );
    });

    return Scaffold(
      appBar: AppBar(title: const Text('Catat Pembayaran')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            // Member dropdown
            AppTextFormField(label: 'Anggota'),
            // Invoice dropdown (filtered by member)
            AppTextFormField(label: 'Invoice Iuran'),
            // Amount
            AppTextFormField(
              label: 'Jumlah',
              controller: _amountController,
              keyboardType: TextInputType.number,
            ),
            // Payment method
            DropdownButtonFormField<String>(
              value: _paymentMethod,
              items: const [
                DropdownMenuItem(value: 'CASH', child: Text('Tunai')),
                DropdownMenuItem(value: 'TRANSFER', child: Text('Transfer')),
                DropdownMenuItem(value: 'QRIS', child: Text('QRIS')),
              ],
              onChanged: (v) => setState(() => _paymentMethod = v!),
            ),
            // Date picker
            ListTile(
              title: const Text('Tanggal Bayar'),
              subtitle: Text(DateFormat('dd MMM yyyy').format(_paidAt)),
              trailing: const Icon(Icons.calendar_today),
              onTap: () async {
                final date = await showDatePicker(
                  context: context,
                  initialDate: _paidAt,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (date != null) setState(() => _paidAt = date);
              },
            ),
            const SizedBox(height: AppSpacing.lg),
            // Submit button
            FilledButton(
              onPressed: formState is _Loading ? null : _submit,
              child: formState is _Loading
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Simpan Pembayaran'),
            ),
          ],
        ),
      ),
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    ref.read(paymentFormProvider.notifier).submit(
      StoreCooperativePaymentRequestData(
        cooperativeMemberId: _selectedMember!.id,
        cooperativeDuesInvoiceId: _selectedInvoice?.id,
        amount: double.parse(_amountController.text),
        paymentMethod: _paymentMethod,
        paidAt: _paidAt.toIso8601String().split('T').first,
      ),
    );
  }
}
```

---

## 4. Navigation Patterns

### Bottom Navigation

```dart
class MainScaffold extends ConsumerWidget {
  final Widget child;

  const MainScaffold({required this.child});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final role = ref.watch(userRoleProvider).valueOrNull ?? UserRole.none;
    final items = _navItemsForRole(role);

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex(context),
        onDestinationSelected: (index) => _onTap(context, items[index].route),
        destinations: items.map((i) => NavigationDestination(
          icon: Icon(i.icon),
          selectedIcon: Icon(i.selectedIcon),
          label: i.label,
        )).toList(),
      ),
    );
  }

  List<_NavItem> _navItemsForRole(UserRole role) => switch (role) {
    UserRole.cooperativeMember => [
      _NavItem(Icons.home_outlined, Icons.home, 'Beranda', '/home'),
      _NavItem(Icons.receipt_long_outlined, Icons.receipt_long, 'Transaksi', '/transactions'),
      _NavItem(Icons.account_balance_wallet_outlined, Icons.account_balance_wallet, 'Pinjaman', '/loans'),
      _NavItem(Icons.person_outlined, Icons.person, 'Profil', '/profile'),
    ],
    UserRole.technician => [
      _NavItem(Icons.home_outlined, Icons.home, 'Beranda', '/home'),
      _NavItem(Icons.build_outlined, Icons.build, 'Work Orders', '/work-orders'),
      _NavItem(Icons.person_outlined, Icons.person, 'Profil', '/profile'),
    ],
    UserRole.employee => [
      _NavItem(Icons.home_outlined, Icons.home, 'Beranda', '/home'),
      _NavItem(Icons.access_time_outlined, Icons.access_time, 'Absensi', '/attendance'),
      _NavItem(Icons.assignment_outlined, Icons.assignment, 'Pengajuan', '/submissions'),
      _NavItem(Icons.person_outlined, Icons.person, 'Profil', '/profile'),
    ],
    _ => [
      _NavItem(Icons.home_outlined, Icons.home, 'Beranda', '/home'),
      _NavItem(Icons.people_outlined, Icons.people, 'Anggota', '/members'),
      _NavItem(Icons.point_of_sale_outlined, Icons.point_of_sale, 'Kasir', '/pos'),
      _NavItem(Icons.bar_chart_outlined, Icons.bar_chart, 'Laporan', '/reports'),
      _NavItem(Icons.person_outlined, Icons.person, 'Profil', '/profile'),
    ],
  };
}

class _NavItem {
  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final String route;

  const _NavItem(this.icon, this.selectedIcon, this.label, this.route);
}
```

---

## 5. Responsive Layout

```dart
class ResponsiveGrid extends StatelessWidget {
  final List<Widget> children;
  final int crossAxisCountMobile;
  final int crossAxisCountTablet;
  final int crossAxisCountDesktop;

  const ResponsiveGrid({
    required this.children,
    this.crossAxisCountMobile = 2,
    this.crossAxisCountTablet = 3,
    this.crossAxisCountDesktop = 4,
  });

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        int crossAxisCount;
        if (constraints.maxWidth >= 1200) {
          crossAxisCount = crossAxisCountDesktop;
        } else if (constraints.maxWidth >= 600) {
          crossAxisCount = crossAxisCountTablet;
        } else {
          crossAxisCount = crossAxisCountMobile;
        }

        return GridView.count(
          crossAxisCount: crossAxisCount,
          mainAxisSpacing: AppSpacing.sm,
          crossAxisSpacing: AppSpacing.sm,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          children: children,
        );
      },
    );
  }
}
```

---

## 6. Animation Patterns

### Page Transition
```dart
CustomTransitionPage(
  child: const MemberDetailScreen(),
  transitionsBuilder: (context, animation, secondaryAnimation, child) {
    return FadeTransition(opacity: animation, child: child);
  },
)
```

### Shimmer Loading for Deferred Data
```dart
// When using deferred props pattern, show shimmer until data arrives
state.when(
  loading: () => SummaryCardSkeleton(),
  error: (_, __) => const SizedBox.shrink(),
  data: (summary) => SummaryCard(
    title: 'Anggota Aktif',
    value: '${summary.activeMembers}',
    icon: Icons.people,
    color: AppColors.primary,
  ),
)
```

---

*Dokumen ini harus diperbarui setiap ada perubahan komponen UI atau design system.*

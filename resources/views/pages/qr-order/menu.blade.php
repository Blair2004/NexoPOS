{{--
    QR Order — Trang thực đơn công khai (Tính năng 1).
    Trang độc lập: CSS inline + JS thuần, không dùng layout.base/Vite/Vue
    để chạy được ngay cả khi máy chủ chưa build assets.
    Dữ liệu món + giá do server render; giỏ hàng xử lý bằng JS, gửi đơn
    qua fetch POST kèm CSRF token. Giá hiển thị chỉ để tham khảo — giá
    tính đơn luôn được server tính lại (QrOrderController::store).
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f6f6f4; color: #1f2937; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 0 12px 120px; }
        header { text-align: center; padding: 20px 0 8px; }
        header h1 { margin: 0; font-size: 24px; }
        header p { margin: 6px 0 0; color: #6b7280; font-size: 14px; }
        .badge { display: inline-block; margin-top: 10px; background: #166534; color: #fff; padding: 6px 14px; border-radius: 999px; font-size: 14px; font-weight: 600; }
        .channels { display: flex; gap: 8px; justify-content: center; margin: 14px 0 4px; }
        .channels button { flex: 1; max-width: 200px; padding: 10px; border: 1px solid #d1d5db; border-radius: 10px; background: #fff; font-size: 15px; cursor: pointer; }
        .channels button.active { background: #166534; color: #fff; border-color: #166534; }
        .staff-row { display: flex; gap: 8px; justify-content: center; margin: 12px 0 4px; }
        .staff-row button { padding: 9px 14px; border-radius: 10px; border: 1px solid #d1d5db; background: #fff; font-size: 14px; cursor: pointer; }
        .search { width: 100%; padding: 11px 12px; margin: 12px 0 10px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; }
        .cats { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; }
        .cats button { white-space: nowrap; padding: 7px 14px; border: 1px solid #d1d5db; border-radius: 999px; background: #fff; font-size: 14px; cursor: pointer; }
        .cats button.active { background: #1f2937; color: #fff; border-color: #1f2937; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px; }
        @media (min-width: 640px) { .grid { grid-template-columns: 1fr 1fr 1fr; } }
        .card { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        .card img { width: 100%; height: 120px; object-fit: cover; background: #e5e7eb; }
        .card .noimg { height: 120px; display: flex; align-items: center; justify-content: center; background: #e5e7eb; font-size: 34px; color: #9ca3af; }
        .card .body { padding: 10px; display: flex; flex-direction: column; flex: 1; }
        .card h3 { margin: 0 0 4px; font-size: 15px; line-height: 1.3; }
        .card p { margin: 0 0 8px; font-size: 12px; color: #6b7280; flex: 1; }
        .card .row { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
        .price { font-weight: 700; font-size: 15px; }
        .add { background: #166534; color: #fff; border: none; border-radius: 8px; padding: 7px 12px; font-size: 14px; cursor: pointer; }
        .qty { display: flex; align-items: center; gap: 8px; }
        .qty button { width: 28px; height: 28px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; font-size: 16px; cursor: pointer; }
        .empty { text-align: center; color: #6b7280; padding: 40px 0; grid-column: 1 / -1; }
        .cartbar { position: fixed; left: 0; right: 0; bottom: 0; background: #fff; border-top: 1px solid #e5e7eb; padding: 12px; display: flex; justify-content: center; }
        .cartbar button { width: 100%; max-width: 720px; background: #166534; color: #fff; border: none; border-radius: 12px; padding: 14px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .cartbar button:disabled { background: #9ca3af; cursor: default; }
        .sheet { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: none; align-items: flex-end; justify-content: center; z-index: 20; }
        .sheet.open { display: flex; }
        .sheet .panel { background: #fff; width: 100%; max-width: 720px; max-height: 88vh; overflow-y: auto; border-radius: 16px 16px 0 0; padding: 16px; }
        .sheet h2 { margin: 0 0 12px; font-size: 19px; }
        .line { display: flex; align-items: center; gap: 8px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .line .nm { flex: 1; }
        .line .nm small { display: block; color: #6b7280; }
        .sheet label { display: block; font-size: 13px; color: #4b5563; margin: 12px 0 4px; }
        .sheet input, .sheet textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; }
        .total { display: flex; justify-content: space-between; font-size: 17px; font-weight: 700; margin: 16px 0 10px; }
        .submit { width: 100%; background: #166534; color: #fff; border: none; border-radius: 12px; padding: 14px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .submit:disabled { background: #9ca3af; cursor: default; }
        .close { width: 100%; background: none; border: none; padding: 12px; font-size: 15px; color: #6b7280; cursor: pointer; }
        .success { text-align: center; padding: 48px 16px; }
        .success .tick { font-size: 56px; }
        .success h2 { font-size: 21px; }
        .success .code { font-size: 26px; font-weight: 800; letter-spacing: 1px; margin: 8px 0; }
        .success a { display: inline-block; margin: 8px 6px 0; color: #166534; font-weight: 600; }
        .toast { position: fixed; left: 50%; bottom: 90px; transform: translateX(-50%); background: #1f2937; color: #fff; padding: 10px 16px; border-radius: 10px; font-size: 14px; display: none; z-index: 30; max-width: 90vw; text-align: center; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="wrap" id="menuWrap">
    <header>
        <h1>{{ $storeName }}</h1>
        @if( $table )
            <p>{{ __( 'Scan the QR code at your table to view the menu and order.' ) }}</p>
            <span class="badge">{{ __( 'Table' ) }} {{ $table }}</span>
        @else
            <p>{{ __( 'Order Online' ) }}</p>
            <div class="channels" id="channels">
                <button type="button" data-channel="takeaway" class="active">{{ __( 'Take Away' ) }}</button>
                <button type="button" data-channel="delivery">{{ __( 'Delivery' ) }}</button>
            </div>
        @endif
        @if( $table )
            <div class="staff-row">
                <button type="button" id="callStaffBtn">📣 {{ __( 'Call Staff' ) }}</button>
                <button type="button" id="requestBillBtn">🧾 {{ __( 'Request Bill' ) }}</button>
            </div>
        @endif
    </header>

    <h2 style="font-size:18px;margin:14px 0 0;">{{ __( 'Menu' ) }}</h2>
    <input class="search" id="search" type="search" placeholder="{{ __( 'Search for a dish...' ) }}">
    <div class="cats" id="cats">
        <button type="button" data-cat="" class="active">{{ __( 'All' ) }}</button>
        @foreach( $categories as $category )
            <button type="button" data-cat="{{ $category->id }}">{{ $category->name }}</button>
        @endforeach
    </div>

    <div class="grid" id="grid">
        @forelse( $products as $product )
            <article class="card" data-cat="{{ $product['category_id'] }}" data-name="{{ mb_strtolower( $product['name'] ) }}"
                     data-id="{{ $product['id'] }}" data-uq="{{ $product['unit_quantity_id'] }}"
                     data-price="{{ $product['price'] }}" data-pname="{{ $product['name'] }}">
                @if( $product['image'] )
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy">
                @else
                    <div class="noimg">🍽️</div>
                @endif
                <div class="body">
                    <h3>{{ $product['name'] }}</h3>
                    @if( $product['description'] )
                        <p>{{ $product['description'] }}</p>
                    @endif
                    <div class="row">
                        <span class="price">{{ $product['price_formatted'] }}</span>
                        <span class="qtyctl"></span>
                    </div>
                </div>
            </article>
        @empty
            <p class="empty">{{ __( 'No products available.' ) }}</p>
        @endforelse
    </div>
</div>

<div class="wrap hidden" id="successWrap">
    <div class="success">
        <div class="tick">✅</div>
        <h2>{{ __( 'Your order has been placed.' ) }}</h2>
        <p>{{ __( 'Your order is being prepared.' ) }}</p>
        <div class="code" id="successCode"></div>
        <div>
            <a href="#" id="statusLink">{{ __( 'View order status' ) }}</a>
            <a href="#" id="backLink">{{ __( 'Back to menu' ) }}</a>
        </div>
        @if( $table )
            <div class="staff-row">
                <button type="button" id="callStaffBtn2">📣 {{ __( 'Call Staff' ) }}</button>
                <button type="button" id="requestBillBtn2">🧾 {{ __( 'Request Bill' ) }}</button>
            </div>
        @endif
    </div>
</div>

<div class="cartbar">
    <button type="button" id="cartBarBtn" disabled>{{ __( 'Your Cart' ) }}</button>
</div>

<div class="sheet" id="sheet">
    <div class="panel">
        <h2>{{ __( 'Your Cart' ) }}</h2>
        <div id="cartLines"></div>
        <div id="cartEmpty" class="empty">{{ __( 'Empty cart' ) }}</div>
        <div id="customerFields">
            <label>{{ __( 'Your Name' ) }}</label>
            <input id="customerName" type="text" maxlength="100" autocomplete="name">
            <label>{{ __( 'Phone Number' ) }}</label>
            <input id="customerPhone" type="tel" maxlength="20" autocomplete="tel">
            <div id="deliveryFields" class="hidden">
                <label>{{ __( 'Delivery Address' ) }}</label>
                <input id="address1" type="text" maxlength="255" autocomplete="street-address">
                <label>{{ __( 'City' ) }}</label>
                <input id="city" type="text" maxlength="100">
            </div>
            <label>{{ __( 'Note' ) }}</label>
            <textarea id="note" rows="2" maxlength="500" placeholder="{{ __( 'Add a note for the kitchen...' ) }}"></textarea>
        </div>
        <div class="total"><span>{{ __( 'Total' ) }}</span><span id="cartTotal"></span></div>
        <button type="button" class="submit" id="submitBtn">{{ __( 'Confirm Order' ) }}</button>
        <button type="button" class="close" id="closeSheet">{{ __( 'Close' ) }}</button>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    const csrf = @json(csrf_token());
    const storeUrl = @json(route('ns.qr-order.store'));
    const requestUrl = @json(route('ns.qr-order.request'));
    const table = @json($table);
    const currency = @json($currency);
    const i18n = {
        yourCart: @json(__('Your Cart')),
        placeOrder: @json(__('Place Order')),
        orderFailed: @json(__('The order could not be placed.')),
        staffNotified: @json(__('Staff has been notified.')),
        needAddress: @json(__('Please enter your delivery address.')),
        add: @json(__('Add')),
        close: @json(__('Close')),
    };
    let channel = table ? 'dine-in' : 'takeaway';
    let lastOrderCode = null;
    const cart = new Map(); // unit_quantity_id -> {id, uq, name, price, qty}

    function fmt(amount) {
        const n = Number(amount).toFixed(currency.precision)
            .replace('.', '\u0000')
            .replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_separator)
            .replace('\u0000', currency.decimal_separator);
        return currency.position === 'after' ? n + ' ' + currency.symbol : currency.symbol + n;
    }

    function toast(msg) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(() => { el.style.display = 'none'; }, 3500);
    }

    function cartCount() {
        let n = 0, total = 0;
        cart.forEach(i => { n += i.qty; total += i.qty * i.price; });
        return { n, total };
    }

    function renderQtyControls() {
        document.querySelectorAll('#grid .card').forEach(card => {
            const uq = card.dataset.uq;
            const item = cart.get(uq);
            const holder = card.querySelector('.qtyctl');
            holder.innerHTML = '';
            if (!item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'add';
                btn.textContent = i18n.add;
                btn.addEventListener('click', () => changeQty(card, 1));
                holder.appendChild(btn);
            } else {
                const wrap = document.createElement('span');
                wrap.className = 'qty';
                const minus = document.createElement('button');
                minus.type = 'button'; minus.textContent = '−';
                minus.addEventListener('click', () => changeQty(card, -1));
                const qty = document.createElement('b');
                qty.textContent = item.qty;
                const plus = document.createElement('button');
                plus.type = 'button'; plus.textContent = '+';
                plus.addEventListener('click', () => changeQty(card, 1));
                wrap.append(minus, qty, plus);
                holder.appendChild(wrap);
            }
        });
        const { n, total } = cartCount();
        const bar = document.getElementById('cartBarBtn');
        bar.disabled = n === 0;
        bar.textContent = n === 0 ? i18n.yourCart : i18n.yourCart + ' (' + n + ') · ' + fmt(total);
        renderCartSheet();
    }

    function changeQty(card, delta) {
        const uq = card.dataset.uq;
        const cur = cart.get(uq);
        const qty = (cur ? cur.qty : 0) + delta;
        if (qty <= 0) {
            cart.delete(uq);
        } else {
            cart.set(uq, {
                id: Number(card.dataset.id),
                uq: Number(uq),
                name: card.dataset.pname,
                price: Number(card.dataset.price),
                qty: qty,
            });
        }
        renderQtyControls();
    }

    function renderCartSheet() {
        const lines = document.getElementById('cartLines');
        lines.innerHTML = '';
        document.getElementById('cartEmpty').style.display = cart.size ? 'none' : 'block';
        cart.forEach(item => {
            const row = document.createElement('div');
            row.className = 'line';
            const nm = document.createElement('div');
            nm.className = 'nm';
            nm.textContent = item.name;
            const sm = document.createElement('small');
            sm.textContent = fmt(item.price);
            nm.appendChild(sm);
            const minus = document.createElement('button');
            minus.type = 'button'; minus.textContent = '−';
            minus.style.cssText = 'width:28px;height:28px;border-radius:8px;border:1px solid #d1d5db;background:#fff;cursor:pointer;';
            minus.addEventListener('click', () => {
                item.qty -= 1;
                if (item.qty <= 0) cart.delete(String(item.uq));
                renderQtyControls();
            });
            const q = document.createElement('b');
            q.textContent = item.qty;
            const plus = document.createElement('button');
            plus.type = 'button'; plus.textContent = '+';
            plus.style.cssText = minus.style.cssText;
            plus.addEventListener('click', () => { item.qty += 1; renderQtyControls(); });
            const sum = document.createElement('b');
            sum.textContent = fmt(item.qty * item.price);
            row.append(nm, minus, q, plus, sum);
            lines.appendChild(row);
        });
        document.getElementById('cartTotal').textContent = fmt(cartCount().total);
    }

    function applyFilter() {
        const q = document.getElementById('search').value.trim().toLowerCase();
        const cat = document.querySelector('#cats button.active').dataset.cat;
        document.querySelectorAll('#grid .card').forEach(card => {
            const okCat = !cat || card.dataset.cat === cat;
            const okQ = !q || card.dataset.name.indexOf(q) !== -1;
            card.style.display = okCat && okQ ? '' : 'none';
        });
    }

    document.getElementById('search').addEventListener('input', applyFilter);
    document.querySelectorAll('#cats button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#cats button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilter();
        });
    });

    const channelBtns = document.querySelectorAll('#channels button');
    channelBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            channelBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            channel = btn.dataset.channel;
            document.getElementById('deliveryFields').classList.toggle('hidden', channel !== 'delivery');
        });
    });

    document.getElementById('cartBarBtn').addEventListener('click', () => {
        document.getElementById('sheet').classList.add('open');
    });
    document.getElementById('closeSheet').addEventListener('click', () => {
        document.getElementById('sheet').classList.remove('open');
    });
    document.getElementById('sheet').addEventListener('click', e => {
        if (e.target.id === 'sheet') e.target.classList.remove('open');
    });

    document.getElementById('submitBtn').addEventListener('click', async () => {
        if (cart.size === 0) return;
        if (channel === 'delivery' && !document.getElementById('address1').value.trim()) {
            toast(i18n.needAddress);
            return;
        }
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        const items = [];
        cart.forEach(i => items.push({ product_id: i.id, unit_quantity_id: i.uq, quantity: i.qty }));
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    channel,
                    table,
                    items,
                    customer_name: document.getElementById('customerName').value.trim(),
                    customer_phone: document.getElementById('customerPhone').value.trim(),
                    note: document.getElementById('note').value.trim(),
                    address_1: document.getElementById('address1').value.trim(),
                    city: document.getElementById('city').value.trim(),
                }),
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') {
                const msg = (data.errors && Object.values(data.errors)[0] && Object.values(data.errors)[0][0]) || data.message || i18n.orderFailed;
                toast(msg);
                return;
            }
            lastOrderCode = data.data.code;
            cart.clear();
            renderQtyControls();
            document.getElementById('sheet').classList.remove('open');
            document.getElementById('menuWrap').classList.add('hidden');
            document.querySelector('.cartbar').classList.add('hidden');
            document.getElementById('successCode').textContent = data.data.code;
            document.getElementById('statusLink').href = data.data.status_url;
            document.getElementById('successWrap').classList.remove('hidden');
            window.scrollTo(0, 0);
        } catch (e) {
            toast(i18n.orderFailed);
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('backLink').addEventListener('click', e => {
        e.preventDefault();
        document.getElementById('successWrap').classList.add('hidden');
        document.getElementById('menuWrap').classList.remove('hidden');
        document.querySelector('.cartbar').classList.remove('hidden');
    });

    async function requestService(kind) {
        try {
            const res = await fetch(requestUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ kind, table, order_code: lastOrderCode }),
            });
            const data = await res.json();
            toast(res.ok && data.status === 'success' ? data.message : (data.message || i18n.orderFailed));
        } catch (e) {
            toast(i18n.orderFailed);
        }
    }

    ['callStaffBtn', 'callStaffBtn2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => requestService('call-staff'));
    });
    ['requestBillBtn', 'requestBillBtn2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => requestService('request-bill'));
    });

    renderQtyControls();
})();
</script>
</body>
</html>

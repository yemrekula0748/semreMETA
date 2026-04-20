@extends('layouts.app')
@section('title', 'DM Kutusu')
@section('page-title', 'DM Kutusu')
@section('no-padding', '')

@section('header-actions')
    @if(isset($selectedAccount) && $accounts->count() > 1)
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 hidden sm:inline">Hesap:</span>
            <select onchange="window.location.href='{{ route('inbox.index') }}?account='+this.value"
                    class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-purple-500">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ $selectedAccount && $selectedAccount->id === $acc->id ? 'selected' : '' }}>
                        {{ $acc->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
@endsection

@section('content')
<div class="flex h-full overflow-hidden" style="height: calc(100vh - 73px);">

    {{-- ===== SOL PANEL: Konuşma Listesi ===== --}}
    <div id="conversationList"
         class="w-full lg:w-80 xl:w-96 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col
                {{ isset($selectedConversation) ? 'hidden lg:flex' : 'flex' }}">

        <!-- Arama -->
        <div class="p-3 border-b border-gray-100">
            <div class="relative">
                <input type="text" id="searchConv" placeholder="Ara..." oninput="filterConversations(this.value)"
                       class="w-full pl-8 pr-3 py-2 text-sm bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition">
                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <!-- Konuşma Listesi -->
        <div id="convListContainer" class="flex-1 overflow-y-auto divide-y divide-gray-50">
            @if($accounts->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p class="font-medium">Erişilebilir hesap yok</p>
                    <p class="text-xs mt-1">Admin ile iletişime geçin</p>
                </div>
            @elseif($conversations->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p>Henüz mesaj yok</p>
                    <p class="text-xs mt-1">Biri yazınca burada görünecek</p>
                </div>
            @else
                @foreach($conversations as $conv)
                <a href="{{ route('inbox.show', $conv) }}"
                   data-name="{{ strtolower($conv->display_name) }}"
                   class="conv-item flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 transition-colors
                          {{ isset($selectedConversation) && $selectedConversation->id === $conv->id ? 'bg-purple-50 border-r-2 border-purple-500' : '' }}">
                    <div class="relative flex-shrink-0">
                        @if($conv->participant_profile_pic)
                            <img src="{{ $conv->participant_profile_pic }}" class="w-11 h-11 rounded-full object-cover" alt="">
                        @else
                            <div class="w-11 h-11 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($conv->display_name, 0, 1)) }}
                            </div>
                        @endif
                        @if($conv->unread_count > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold px-1">
                                {{ $conv->unread_count > 9 ? '9+' : $conv->unread_count }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900 truncate {{ $conv->unread_count > 0 ? 'font-bold' : '' }}">
                                {{ $conv->display_name }}
                            </p>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $conv->last_message_at?->format('H:i') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                            @if($conv->latestMessage)
                                @if($conv->latestMessage->is_outgoing) <span class="text-gray-400">Sen: </span>@endif
                                @if($conv->latestMessage->message_type === 'image') 📷 Resim
                                @else {{ Str::limit($conv->latestMessage->message_text, 40) }}
                                @endif
                            @else
                                <span class="text-gray-400 italic">Yeni konuşma</span>
                            @endif
                        </p>
                    </div>
                </a>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ===== SAĞ PANEL: Mesaj Ekranı ===== --}}
    <div id="messagePanel" class="flex-1 flex flex-col {{ !isset($selectedConversation) ? 'hidden lg:flex' : 'flex' }}">

        @if(!isset($selectedConversation))
            {{-- Boş durum --}}
            <div class="flex-1 flex items-center justify-center bg-gray-50">
                <div class="text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p class="font-medium">Bir konuşma seçin</p>
                    <p class="text-sm mt-1">Sol listeden bir konuşmaya tıklayın</p>
                </div>
            </div>
        @else
            {{-- Konuşma Header --}}
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0">
                <button onclick="showConversationList()" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 mr-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                @if($selectedConversation->participant_profile_pic)
                    <img src="{{ $selectedConversation->participant_profile_pic }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="">
                @else
                    <div class="w-9 h-9 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($selectedConversation->display_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ $selectedConversation->display_name }}</p>
                    @if($selectedConversation->participant_username)
                        <p class="text-xs text-gray-500">@{{ $selectedConversation->participant_username }}</p>
                    @endif
                </div>
                <div class="ml-auto">
                    <span class="text-xs text-gray-400">{{ $selectedConversation->instagramAccount->name }}</span>
                </div>
            </div>

            {{-- Mesajlar --}}
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                @foreach($messages as $message)
                    @include('inbox._message', ['message' => $message])
                @endforeach
                <div id="messagesEnd"></div>
            </div>

            {{-- Mesaj Gönderme Formu --}}
            @can('send_messages')
            <div class="bg-white border-t border-gray-200 p-3 flex-shrink-0">
                {{-- Resim önizleme --}}
                <div id="imagePreview" class="hidden mb-2 relative inline-block">
                    <img id="previewImg" src="" class="h-20 rounded-lg object-cover" alt="">
                    <button onclick="clearImage()" type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="messageForm" action="{{ route('inbox.send', $selectedConversation) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" id="imageInput" name="image" accept="image/*" class="hidden" onchange="previewImage(event)">
                    <div class="flex items-end gap-2">
                        <button type="button" onclick="document.getElementById('imageInput').click()"
                                class="p-2.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-xl transition-colors flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>
                        <textarea id="messageInput" name="message" rows="1" placeholder="Mesaj yaz..."
                                  onkeydown="handleKeyDown(event)"
                                  oninput="this.style.height='auto';this.style.height=(this.scrollHeight)+'px'"
                                  class="flex-1 resize-none border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent max-h-28 overflow-y-auto"></textarea>
                        <button type="submit" id="sendBtn"
                                class="p-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-colors flex-shrink-0 disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 text-center text-xs text-gray-400">
                Mesaj gönderme yetkiniz yok
            </div>
            @endcan
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// ---- Konuşma listesi filtrele ----
function filterConversations(val) {
    const q = val.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(el => {
        el.classList.toggle('hidden', !el.dataset.name.includes(q));
    });
}

// ---- Mobil: geri git ----
function showConversationList() {
    document.getElementById('conversationList').classList.remove('hidden');
    document.getElementById('conversationList').classList.add('flex');
    document.getElementById('messagePanel').classList.add('hidden');
}

// ---- Textarea Enter ile gönder ----
function handleKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('messageForm').submit();
    }
}

// ---- Resim önizleme ----
function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imagePreview').classList.remove('hidden');
        document.getElementById('messageInput').placeholder = 'İsteğe bağlı başlık ekle...';
    };
    reader.readAsDataURL(file);
}

function clearImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('messageInput').placeholder = 'Mesaj yaz...';
}

// ---- Scroll to bottom ----
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) container.scrollTop = container.scrollHeight;
}

scrollToBottom();

@if(isset($selectedConversation))
// ---- POLLING: yeni mesajları 5 saniyede bir kontrol et ----
let lastMessageTime = '{{ $messages->last()?->sent_at?->toIso8601String() ?? '' }}';
const convId = {{ $selectedConversation->id }};

function messageHtml(msg) {
    const align = msg.is_outgoing ? 'justify-end' : 'justify-start';
    const bubble = msg.is_outgoing
        ? 'bg-purple-600 text-white rounded-tl-2xl rounded-bl-2xl rounded-tr-sm'
        : 'bg-white text-gray-800 border border-gray-200 rounded-tr-2xl rounded-br-2xl rounded-tl-sm';
    let content = '';
    if (msg.message_type === 'image' && msg.media_url) {
        content = `<a href="${msg.media_url}" target="_blank"><img src="${msg.media_url}" class="max-w-xs rounded-xl" onerror="this.src=''" /></a>`;
        if (msg.message_text) content += `<p class="text-sm mt-1">${msg.message_text}</p>`;
    } else {
        content = `<p class="text-sm whitespace-pre-wrap">${msg.message_text || ''}</p>`;
    }
    return `<div class="flex ${align}">
        <div class="max-w-xs sm:max-w-sm px-4 py-2.5 rounded-2xl shadow-sm ${bubble}">
            ${content}
            <p class="text-xs mt-1 ${msg.is_outgoing ? 'text-purple-200' : 'text-gray-400'}">${msg.sent_at_human}</p>
        </div>
    </div>`;
}

async function pollMessages() {
    try {
        const url = `/inbox/${convId}/poll` + (lastMessageTime ? `?since=${encodeURIComponent(lastMessageTime)}` : '');
        const res = await fetch(url, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        if (!res.ok) return;
        const data = await res.json();
        if (data.messages && data.messages.length > 0) {
            const container = document.getElementById('messagesContainer');
            const end = document.getElementById('messagesEnd');
            data.messages.forEach(msg => {
                const div = document.createElement('div');
                div.innerHTML = messageHtml(msg);
                container.insertBefore(div.firstChild, end);
                lastMessageTime = msg.sent_at;
            });
            scrollToBottom();
        }
    } catch (e) {}
}

setInterval(pollMessages, 5000);
@endif
</script>
@endpush

<div class="flex {{ $message->is_outgoing ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-xs sm:max-w-sm px-4 py-2.5 rounded-2xl shadow-sm
        {{ $message->is_outgoing
            ? 'bg-purple-600 text-white rounded-tl-2xl rounded-bl-2xl rounded-tr-sm'
            : 'bg-white text-gray-800 border border-gray-200 rounded-tr-2xl rounded-br-2xl rounded-tl-sm' }}">

        @if($message->message_type === 'image' && $message->media_url)
            <a href="{{ $message->media_url }}" target="_blank" class="block">
                <img src="{{ $message->media_url }}"
                     class="max-w-full rounded-xl cursor-pointer hover:opacity-90 transition"
                     onerror="this.parentElement.innerHTML='<span class=\'text-xs opacity-70\'>📷 Resim yüklenemedi</span>'"
                     alt="Resim">
            </a>
            @if($message->message_text)
                <p class="text-sm mt-2 whitespace-pre-wrap">{{ $message->message_text }}</p>
            @endif
        @elseif(in_array($message->message_type, ['video', 'audio']))
            <p class="text-sm opacity-80">
                @if($message->message_type === 'video') 🎥 Video
                @else 🎵 Ses
                @endif
                @if($message->media_url)
                    — <a href="{{ $message->media_url }}" target="_blank" class="underline">Aç</a>
                @endif
            </p>
        @else
            <p class="text-sm whitespace-pre-wrap">{{ $message->message_text }}</p>
        @endif

        <p class="text-xs mt-1.5 {{ $message->is_outgoing ? 'text-purple-200' : 'text-gray-400' }}">
            {{ $message->sent_at->format('H:i') }}
            @if($message->is_outgoing)
                <svg class="w-3 h-3 inline ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            @endif
        </p>
    </div>
</div>

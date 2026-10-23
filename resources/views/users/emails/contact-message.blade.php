<p><strong>New contact message received.</strong></p>

<p><strong>Name:</strong> {{ $contactMessage->name }}</p>
<p><strong>Email:</strong> {{ $contactMessage->email }}</p>
<p><strong>Subject:</strong> {{ $contactMessage->subject }}</p>

<p><strong>Message:</strong></p>
<p>{!! nl2br(e($contactMessage->message)) !!}</p>

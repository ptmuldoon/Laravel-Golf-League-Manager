<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Nines - {{ $course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .back-link { display: inline-block; color: white; text-decoration: none; padding: 10px 20px;
            background: rgba(255,255,255,0.2); border-radius: 5px; margin-bottom: 20px; }
        .card { background: white; padding: 26px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); font-size: 1.7em; margin-bottom: 6px; }
        h2 { color: var(--primary-color); font-size: 1.2em; margin-bottom: 14px; }
        .subtitle { color: #666; margin-bottom: 18px; font-size: 0.95em; }
        label { display: block; font-weight: 600; color: #333; margin-bottom: 5px; font-size: 0.85em; }
        input[type="text"], input[type="number"] { width: 100%; padding: 9px 11px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .row > div { flex: 1; min-width: 120px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9em; margin-bottom: 8px; }
        th, td { border: 1px solid #e0e0e0; padding: 5px; text-align: center; }
        th { background: var(--primary-light); color: var(--primary-color); }
        .holes-grid th { font-size: 0.78em; }
        .holes-grid input { padding: 6px 4px; text-align: center; border-width: 1px; }
        .btn { display: inline-block; padding: 11px 22px; border: none; border-radius: 8px; font-size: 0.95em;
            font-weight: 600; cursor: pointer; background: var(--primary-color); color: white; text-decoration: none; }
        .btn-danger { background: #dc3545; padding: 6px 12px; font-size: 0.85em; }
        .errors { background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; }
        .success { background: #d4edda; color: #155724; padding: 12px 15px; border-radius: 8px; margin-bottom: 16px; }
        .nine-block { border: 1px solid #eee; border-radius: 10px; padding: 16px; margin-bottom: 14px; }
        .nine-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .empty { color: #888; padding: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.courses.show', $course->id) }}" class="back-link">&larr; Back to Course</a>

        @if(session('success'))<div class="success">&check; {{ session('success') }}</div>@endif
        @if($errors->any())<div class="errors">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="card">
            <h1>{{ $course->name }} &mdash; Nines</h1>
            <div class="subtitle">Define the individual nines for a multi-nine facility (e.g. a 27-hole course). A league match can then be scheduled over any two of them. Standard 18-hole courses don't need nines.</div>

            @if($course->nines->isEmpty())
                <div class="empty">No nines defined yet. Add one below.</div>
            @else
                @foreach($course->nines as $nine)
                    @php $holes = $nine->courseInfo->sortBy('hole_number'); $tb = $holes->first(); @endphp
                    <div class="nine-block">
                        <div class="nine-head">
                            <h2 style="margin:0;">{{ $nine->name }}
                                <span style="font-weight:400; color:#888; font-size:0.8em;">
                                    @if($tb) &bull; {{ $tb->teebox }} &bull; Rating {{ $tb->rating }} / Slope {{ $tb->slope }} &bull; Par {{ $holes->sum('par') }}@endif
                                </span>
                            </h2>
                            <form action="{{ route('admin.courses.nines.delete', [$course->id, $nine->id]) }}" method="POST" onsubmit="return confirm('Delete nine {{ $nine->name }}?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                        @if($holes->isNotEmpty())
                            <table>
                                <tr><th>Hole</th>@foreach($holes as $h)<th>{{ $h->hole_number }}</th>@endforeach<th>Tot</th></tr>
                                <tr><td><strong>Par</strong></td>@foreach($holes as $h)<td>{{ $h->par }}</td>@endforeach<td>{{ $holes->sum('par') }}</td></tr>
                                <tr><td><strong>SI</strong></td>@foreach($holes as $h)<td>{{ $h->handicap ?? '-' }}</td>@endforeach<td>-</td></tr>
                                <tr><td><strong>Yds</strong></td>@foreach($holes as $h)<td>{{ $h->yardage ?? '-' }}</td>@endforeach<td>{{ $holes->sum('yardage') ?: '-' }}</td></tr>
                            </table>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        <div class="card">
            <h2>Add a Nine</h2>
            <form action="{{ route('admin.courses.nines.add', $course->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div><label>Nine Name</label><input type="text" name="name" placeholder="e.g. Ocean" value="{{ old('name') }}" required></div>
                    <div><label>Tee Box</label><input type="text" name="teebox" placeholder="e.g. White" value="{{ old('teebox', $course->default_teebox ?? 'White') }}" required></div>
                    <div><label>9-Hole Rating</label><input type="number" name="rating" step="0.1" min="20" max="45" placeholder="35.5" value="{{ old('rating') }}" required></div>
                    <div><label>9-Hole Slope</label><input type="number" name="slope" step="1" min="55" max="155" placeholder="120" value="{{ old('slope') }}" required></div>
                </div>

                <label>Holes (Par / Stroke Index / Yardage)</label>
                <table class="holes-grid">
                    <tr><th>Hole</th>@for($i=1;$i<=9;$i++)<th>{{ $i }}</th>@endfor</tr>
                    <tr><td><strong>Par</strong></td>@for($i=0;$i<9;$i++)<td><input type="number" name="pars[{{ $i }}]" min="3" max="6" value="{{ old('pars.'.$i, 4) }}" required></td>@endfor</tr>
                    <tr><td><strong>SI</strong></td>@for($i=0;$i<9;$i++)<td><input type="number" name="handicaps[{{ $i }}]" min="1" max="9" value="{{ old('handicaps.'.$i, $i+1) }}"></td>@endfor</tr>
                    <tr><td><strong>Yds</strong></td>@for($i=0;$i<9;$i++)<td><input type="number" name="yardages[{{ $i }}]" min="50" max="700" value="{{ old('yardages.'.$i) }}"></td>@endfor</tr>
                </table>
                <div style="margin-top:14px;"><button type="submit" class="btn">Add Nine</button></div>
            </form>
        </div>
    </div>
</body>
</html>

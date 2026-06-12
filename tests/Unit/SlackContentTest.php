<?php

use App\Support\SlackContent;

it('converts newlines to line breaks', function () {
    expect(SlackContent::render("line one\nline two"))
        ->toBe('line one<br>line two');
});

it('collapses three or more newlines into two', function () {
    expect(SlackContent::render("a\n\n\n\nb"))
        ->toBe('a<br><br>b');
});

it('renders bold, italic and strikethrough', function () {
    expect(SlackContent::render('this is *bold* and _italic_ and ~gone~'))
        ->toBe('this is <strong>bold</strong> and <em>italic</em> and <del>gone</del>');
});

it('bolds adjacent starred segments like real slack messages', function () {
    expect(SlackContent::render('*MARCH26: (-10.19%)* *MaxDD:(-15.75%).* *CurrDD: (-14.14%).*'))
        ->toBe('<strong>MARCH26: (-10.19%)</strong> <strong>MaxDD:(-15.75%).</strong> <strong>CurrDD: (-14.14%).</strong>');
});

it('does not italicize snake_case words or bold bare asterisks', function () {
    expect(SlackContent::render('use the slack_user_id column'))
        ->toBe('use the slack_user_id column')
        ->and(SlackContent::render('5 * 3 = 15'))
        ->toBe('5 * 3 = 15');
});

it('renders inline code and protects its contents', function () {
    expect(SlackContent::render('March: `-9.28%` and `*not bold*`'))
        ->toBe('March: <code class="msg-code">-9.28%</code> and <code class="msg-code">*not bold*</code>');
});

it('renders fenced code blocks preserving inner newlines and markdown characters', function () {
    $rendered = SlackContent::render("before\n```\nfirst *line*\nsecond _line_\n```\nafter");

    expect($rendered)
        ->toBe("before<br><pre class=\"msg-pre\"><code>first *line*\nsecond _line_</code></pre><br>after");
});

it('renders consecutive quoted lines as a single blockquote', function () {
    expect(SlackContent::render("&gt; first quoted\n&gt; second quoted\nreply text"))
        ->toBe('<blockquote class="msg-quote">first quoted<br>second quoted</blockquote>reply text');
});

it('turns imported mention tags into profile search links', function () {
    expect(SlackContent::render('ping <strong>@Rajan Raju</strong> here'))
        ->toBe('ping <a href="/users?q=Rajan%20Raju" class="msg-mention" data-inertia>@Rajan Raju</a> here');
});

it('leaves imported anchors untouched even when they contain markdown characters', function () {
    $anchor = '<a href="https://x.com/some_user/status/1?s=20" target="_blank">https://x.com/some_user/status/1?s=20</a>';

    expect(SlackContent::render("Revolutionary \n{$anchor}"))
        ->toBe("Revolutionary <br>{$anchor}");
});

it('keeps pre-escaped entities intact', function () {
    expect(SlackContent::render('Slack deletes everything &gt; 1 year &amp; more'))
        ->toBe('Slack deletes everything &gt; 1 year &amp; more');
});

it('renders an empty string unchanged', function () {
    expect(SlackContent::render(''))->toBe('');
});

<?php
use PHPUnit\Framework\TestCase;
use \Nullai\Vista\View;
use \Nullai\Vista\Engines\ViewRenderEngine;

class TestVista extends TestCase
{
    public function testViewClassConstructor()
    {
        $this->assertInstanceOf(View::class, new View('test'));
    }

    public function testViewPaths()
    {
        // using dot notation
        $view = new View('test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);

        // using override folder prefix with : delimiter, in dot notation
        $view = new View(__DIR__ . '/views' .':test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);

        // using direct file path
        $view = new View(__DIR__ . '/views/test.php');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);
    }

    public function testViewContent()
    {
        $view = new View('test');

        $this->assertStringContainsString('test file &amp;', $view->content());
    }

    public function testViewContentRelativeLookup()
    {
        $view = new View(':test');

        $this->assertStringContainsString('test file &amp;', $view->content());
    }

    public function testViewEngineClass()
    {
        $view = new View('engine-access');

        $this->assertEquals(ViewRenderEngine::class, $view->engine);
    }

    public function testViewEngineContent()
    {
        $view = new View('engine-access');

        $this->assertEquals($view->fullPath, $view->content());
    }

    public function testViewEngineLayoutWithContent()
    {
        $view = new View('with-layout', ['content' => 'content body']);
        $content = $view->content();

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithInclude()
    {
        $view = new View('with-layout-and-include', ['content' => 'content body']);
        $content = $view->content();

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithIncludeIfAndSlugify()
    {
        $view = new View('with-layout-and-include-if', ['content' => 'content body']);
        $content = (string) $view;

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringEndsWith('html>', $content);
    }

    public function testViewEngineLayoutWithTitleAndJsonEscape()
    {
        $view = new View('with-layout-and-title', ['content' => 'content body', 'title' => 'test title']);
        $content = (string) $view;

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringContainsString('<title>test title</title>', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringContainsString('console.log({"site":"\u003CMy Site\u003E"});', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringEndsWith('html>', $content);
    }

    public function testViewEngineRelativeIncludeNestWithGlobalAndLocalVars()
    {
        $view = new View('nest.level-two', ['content' => 'nested']);
        $content = $view->content();

        $this->assertEquals('nested3test file &amp;', $content);
    }

    public function testViewEngineSanitizeAttributes()
    {
        $engine = new ViewRenderEngine(new View('test'));
        $content = $engine->escAttr('<&">');
        $this->assertEquals('&lt;&amp;&quot;&gt;', $content);
    }

    public function testViewEngineSanitizeHtml()
    {
        $engine = new ViewRenderEngine(new View('test'));
        $content = $engine->escHtml('<&">');
        $this->assertEquals('&lt;&amp;"&gt;', $content);
    }

    public function testViewEngineSanitizeJson()
    {
        $engine = new ViewRenderEngine(new View('test'));
        $content = $engine->escJson(['site' => '<My <a> " & Site> & " >>']);
        $this->assertEquals('{"site":"\u003CMy \u003Ca\u003E \u0022 \u0026 Site\u003E \u0026 \u0022 \u003E\u003E"}', $content);
    }

    public function testViewEngineAllowTags()
    {
        $engine = new ViewRenderEngine(new View('test'));
        $content = $engine->allowTags(
            "<script>alert('test');</script><a href=\"<script></script>\">Link</a>",
            ['a' => ['href']]
        );
        $this->assertEquals('<a href="<script></script>">Link</a>', $content);
    }

    public function testViewEngineAllowTagsWithAttributes()
    {
        $engine = new ViewRenderEngine(new View('test'));
        $content = $engine->allowTags(
            "<script>alert('test');</script><A HREF=\"'#'\" styLe='<script>alert(\"true\");</script>'>Link</A>",
            'a:href|style,br,p,ol,ul,figure:src'
        );
        $this->assertEquals('<a href="\'#\'" style="<script>alert(&quot;true&quot;);</script>">Link</a>', $content);

        $content = $engine->allowTags(
            "<script>alert('test');</script><A HREF='#' styLe='content: \"main\"'>Link</A><br>",
            'a:href|style,br'
        );
        $this->assertEquals('<a href="#" style="content: &quot;main&quot;">Link</a><br>', $content);
    }
}

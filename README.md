# Markaround

A better Markdown (at least for me) with consistence linking, content embeding, only one way to do something (I'm looking at you Markdown headers, lists, bold, emphasis), github style code blocks for easy copy-paste, header anchors, footer reference and strikethrough (because you always change your mind).

* \*bold\*
* \_emphasis\_
* -strikethrough-
* \`inline code\`
* \`\`\`  
    code block  
    \`\`\`
* \# Header 1 {#id}  
    \#\# Header 2  
    ...
* \* unordered list item 1  
    \* unordered list item 2
* 1\. ordered list item 1  
    2\. ordered list item 2
* Link
    * http://link
    * \[http://link "title"]
    * \(Link Text)\[http://link]
    * \(Link Text)\[1]
      * [1]: http://link "title"
    * Some Text \[^1]
      * [^1]: http://link "title"
* Embed
  * \<img_url>
  * \<(video|slide)_url> `note: use oembed`
  * \<img_url>\[http://link "title"]
  * \<img_url>\[1]
      * [1]: http://link "title"
  * \<img_url “alt text“>\[http://link “title”]
* \> blockquote



## References:
* http://xkcd.com/927/
* http://johnmacfarlane.net/pandoc/demo/example9/pandocs-markdown.html
* http://www.wikimatrix.org/syntax.php?i=116&x=51&y=7
* http://www.wikicreole.org/wiki/Reasoning
* https://en.wikipedia.org/wiki/Lightweight_markup_language
* http://tantek.pbworks.com/w/page/59905776/Markdown
* http://c2.com/cgi/wiki?WikiDesignPrinciples
* http://c2.com/cgi/wiki?PrincipalComponentAnalysis


## Alternative names
* Mundane - inspired by the Wiki Design Principle of the same name: A small number of \(irregular\) text conventions will provide access to the most useful page markup
* flowt
* flowtext
* markforward
* markfwd
* markright
* markleft
* markrl (rl = rightleft)
* expressive
* pot (plain old text)
* syntext
* textmark
* lightmark
* plaintax

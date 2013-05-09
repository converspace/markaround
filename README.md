# flowt

A better Markdown (at least for me) with consistence linking, content embeding, github style code blocks for easy copy-paste, header anchors, footer reference and strikethrough (because you always change your mind).

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
  * \<img_link>
  * \<(video|slide)_link> `note: use oembed`
  * \<img_link>\[link_to "title"]
  * \<img_link>\[1]
      * [1]: link_to "title"
  * \<img_link “alt text“>\[link “title”]
* \> blockquote



## References:
* http://xkcd.com/927/
* http://johnmacfarlane.net/pandoc/demo/example9/pandocs-markdown.html
* http://www.wikimatrix.org/syntax.php?i=116&x=51&y=7
* http://www.wikicreole.org/wiki/Reasoning
* https://en.wikipedia.org/wiki/Lightweight_markup_language
* http://tantek.pbworks.com/w/page/59905776/Markdown

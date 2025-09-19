<?php if (isset($_vPageContent)) { ?>
<script>
    <?php if (!isset($_vPageContentEditMode))
    {
        $_vPageContentEditMode = false;
    }?>
    var pageContent = <?php echo json_encode($_vPageContent->data); ?>;


    UpdatePageContent(pageContent);


    <?php if ($_vPageContentEditMode) { ?>

        function SaveSubtitleModal(contentIndex)
        {
            var str = $("#content-edit-subtitle-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditSubtitle").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveHeaderModal(contentIndex)
        {
            var str = $("#content-edit-header-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditHeader").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveButtonModal(contentIndex)
        {
            var label = $("#content-edit-button-label").val();
            var url = $("#content-edit-button-url").val();
            SetContentElementDataValue(contentIndex, label, "data", 0);
            SetContentElementDataValue(contentIndex, url, "data", 1);
            $("#modalEditButton").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveTitleModal(contentIndex)
        {
            var str = $("#content-edit-title-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditTitle").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveParagraphModal(contentIndex)
        {
            var str = $("#content-edit-paragraph-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditParagraph").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveMarkdownModal(contentIndex)
        {
            var str = $("#content-edit-markdown-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditMarkdown").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveCodeModal(contentIndex)
        {
            var str = $("#content-edit-code-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditCode").modal("hide");
            UpdatePageContent(pageContent);
        }
        function SaveListModal(contentIndex) {
            var contentToEdit = pageContent[contentIndex];
            var items = contentToEdit.data_items;
            contentToEdit.updated = true;
            console.log(items);

            const itemsArray = ExtractItemsFromListModal();
            console.log(itemsArray);  // e.g., ["item 1", "item 2", "item 3"]

            // Loop through itemsArray to handle insertions and updates
            itemsArray.forEach((dataItem, index) => {
                if (index < items.length) { // If the item exists at the current index
                    if (items[index].data !== dataItem) {
                        items[index].data = dataItem; // Update the data
                        items[index].updated = true;  // Set the updated flag
                    }
                    // Remove the 'deleted' flag if it was set in a previous operation
                    delete items[index].deleted;
                } else { // New item to be inserted
                    items.push({ data: dataItem, inserted: true });
                }
            });

            // Mark remaining items in 'items' (that don't exist in 'itemsArray') as deleted
            for (let i = itemsArray.length; i < items.length; i++) {
                items[i].deleted = true;
            }

            console.log(items);
            $("#modalEditList").modal("hide");
            UpdatePageContent(pageContent);
        }



        function SaveYoutubeModal(contentIndex)
        {
            var str = $("#content-edit-youtube-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditYoutube").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveSketchFabModal(contentIndex)
        {
            var str = $("#content-edit-sketchfab-textbox").val();
            SetContentElementDataValue(contentIndex, str);
            $("#modalEditSketchFab").modal("hide");
            UpdatePageContent(pageContent);
        }

        function SaveMediaModal(contentIndex) {
            
            var str = $("#content-edit-media-textbox").val();
            var media_id = $("#content-edit-media-id").val();
            var image_path = $("#content-edit-media-image").attr("src");
            image_path = removePrefix(image_path, "/assets/media/");
            SetContentElementDataValue(contentIndex, str);
            SetContentElementDataValue(contentIndex, image_path, "image_path");
            SetContentElementDataValue(contentIndex, media_id, "media_id");
            $("#modalEditMedia").modal("hide");
            UpdatePageContent(pageContent);
        }

        function ExtractItemsFromListModal() 
        {
            const parentElement = document.getElementById('content-edit-list-preview');
            const inputElements = parentElement.querySelectorAll('input.form-control');
            
            const items = [];
            inputElements.forEach(input => {
                items.push(input.value);
            });
            
            return items;
        }

        function UpdateYoutubeModalPreview() 
        {
            var video_id = $("#content-edit-youtube-textbox").val();
            var video_url = "https://www.youtube.com/embed/"+video_id+"?rel=0";
            $("#content-edit-youtube-preview-iframe").attr("src",video_url);
        }

        function UpdateSketchFabModalPreview()
        {
            var video_id = $("#content-edit-sketchfab-textbox").val();
            var video_url = "https://sketchfab.com/models/"+video_id+"/embed";
            $("#content-edit-sketchfab-preview-iframe").attr("src",video_url);
        }

        function HandleMarkdownEditorInput()
        {
            if ($("#markdown-view-toggle-preview").hasClass("active"))
            {
                UpdateMarkdownModalPreview();
            }
        }

        function UpdateMarkdownModalPreview()
        {
            var markdown = $("#content-edit-markdown-textbox").val() || "";
            var html = renderMarkdownToHtml(markdown);
            $("#content-edit-markdown-preview").html(html);
        }

        function SetMarkdownEditorView(view)
        {
            var codeButton = $("#markdown-view-toggle-code");
            var previewButton = $("#markdown-view-toggle-preview");
            var textarea = $("#content-edit-markdown-textbox");
            var preview = $("#content-edit-markdown-preview");

            if (view === "preview")
            {
                codeButton.removeClass("active");
                previewButton.addClass("active");
                textarea.addClass("d-none");
                preview.removeClass("d-none");
                UpdateMarkdownModalPreview();
            }
            else
            {
                previewButton.removeClass("active");
                codeButton.addClass("active");
                preview.addClass("d-none");
                textarea.removeClass("d-none");
                textarea.trigger("focus");
            }
        }

        function GetMarkdownTextarea()
        {
            return document.getElementById("content-edit-markdown-textbox");
        }

        function ApplyMarkdownWrap(prefix, suffix, placeholder = "")
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            if (start == null || end == null)
            {
                start = textarea.value.length;
                end = start;
            }

            var selectedText = textarea.value.substring(start, end);
            if (!selectedText)
            {
                selectedText = placeholder;
            }

            var replacement = prefix + selectedText + suffix;
            textarea.setRangeText(replacement, start, end, "end");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function ApplyMarkdownBlock(prefix, suffix, placeholder = "")
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            if (start == null || end == null)
            {
                start = textarea.value.length;
                end = start;
            }

            var selectedText = textarea.value.substring(start, end);
            if (!selectedText)
            {
                selectedText = placeholder;
            }

            var needsLeadingNewline = start > 0 && textarea.value[start - 1] !== "\n";
            var needsTrailingNewline = end < textarea.value.length && textarea.value[end] !== "\n";

            var leading = needsLeadingNewline ? "\n" : "";
            var trailing = needsTrailingNewline ? "\n" : "";

            var replacement = leading + prefix + selectedText + suffix + trailing;
            textarea.setRangeText(replacement, start, end, "end");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function ApplyMarkdownHeading(level)
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? textarea.value.length;
            var value = textarea.value;

            var lineStart = value.lastIndexOf("\n", start - 1) + 1;
            var lineEndIndex = value.indexOf("\n", end);
            if (lineEndIndex === -1)
            {
                lineEndIndex = value.length;
            }

            var selectedText = value.substring(lineStart, lineEndIndex);
            var lines = selectedText.split(/\r?\n/);
            var prefix = "#".repeat(Math.max(1, Math.min(level, 6))) + " ";

            var transformed = lines.map(function(line)
            {
                if (!line.trim())
                {
                    return line;
                }

                var trimmed = line.trimStart();
                trimmed = trimmed.replace(/^#{1,6}\s+/, "");
                var leadingWhitespace = line.substring(0, line.length - trimmed.length);
                return leadingWhitespace + prefix + trimmed;
            }).join("\n");

            textarea.setRangeText(transformed, lineStart, lineEndIndex, "select");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function ApplyMarkdownPrefix(prefix)
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? textarea.value.length;
            var value = textarea.value;

            var selectionStart = value.lastIndexOf("\n", start - 1) + 1;
            var selectionEnd = value.indexOf("\n", end);
            if (selectionEnd === -1)
            {
                selectionEnd = value.length;
            }

            var selectedText = value.substring(selectionStart, selectionEnd);
            var lines = selectedText.split(/\r?\n/);

            var transformed = lines.map(function(line)
            {
                if (!line.trim())
                {
                    return line;
                }

                var trimmed = line.trimStart();
                var leadingWhitespace = line.substring(0, line.length - trimmed.length);
                if (trimmed.startsWith(prefix))
                {
                    return line;
                }

                return leadingWhitespace + prefix + trimmed;
            }).join("\n");

            textarea.setRangeText(transformed, selectionStart, selectionEnd, "select");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function ApplyMarkdownList(type)
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? textarea.value.length;
            var value = textarea.value;

            var selectionStart = value.lastIndexOf("\n", start - 1) + 1;
            var selectionEnd = value.indexOf("\n", end);
            if (selectionEnd === -1)
            {
                selectionEnd = value.length;
            }

            var selectedText = value.substring(selectionStart, selectionEnd);
            var lines = selectedText.split(/\r?\n/);

            var transformed = lines.map(function(line, index)
            {
                if (!line.trim())
                {
                    return line;
                }

                var trimmed = line.trimStart();
                var leadingWhitespace = line.substring(0, line.length - trimmed.length);

                if (type === "ordered")
                {
                    trimmed = trimmed.replace(/^\d+\.\s+/, "");
                    return leadingWhitespace + (index + 1) + ". " + trimmed;
                }

                if (type === "task")
                {
                    trimmed = trimmed.replace(/^[-*+]\s+\[[ xX]?\]\s+/, "");
                    return leadingWhitespace + "- [ ] " + trimmed;
                }

                trimmed = trimmed.replace(/^[-*+]\s+/, "");
                return leadingWhitespace + "- " + trimmed;
            }).join("\n");

            textarea.setRangeText(transformed, selectionStart, selectionEnd, "select");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function InsertMarkdownLink()
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? start;
            var selectedText = textarea.value.substring(start, end) || "link text";
            var urlPlaceholder = "https://example.com";
            var replacement = "[" + selectedText + "](" + urlPlaceholder + ")";

            textarea.setRangeText(replacement, start, end, "end");
            var cursorPosition = start + replacement.length - (urlPlaceholder.length + 1);
            textarea.setSelectionRange(cursorPosition, cursorPosition + urlPlaceholder.length);
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function InsertMarkdownImage()
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? start;
            var selectedText = textarea.value.substring(start, end) || "alt text";
            var urlPlaceholder = "https://example.com/image.png";
            var replacement = "![" + selectedText + "](" + urlPlaceholder + ")";

            textarea.setRangeText(replacement, start, end, "end");
            var cursorPosition = start + replacement.length - (urlPlaceholder.length + 1);
            textarea.setSelectionRange(cursorPosition, cursorPosition + urlPlaceholder.length);
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function InsertMarkdownHorizontalRule()
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? start;
            textarea.setRangeText("\n\n---\n\n", start, end, "end");
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function InsertMarkdownTable()
        {
            var textarea = GetMarkdownTextarea();
            if (!textarea)
            {
                return;
            }

            var start = textarea.selectionStart ?? 0;
            var end = textarea.selectionEnd ?? start;
            var tableTemplate = "\n\n| Column 1 | Column 2 | Column 3 |\n| --- | --- | --- |\n| Row 1 | Data | Data |\n\n";
            textarea.setRangeText(tableTemplate, start, end, "end");
            var cursorPosition = start + tableTemplate.indexOf("Row 1");
            textarea.setSelectionRange(cursorPosition, cursorPosition + "Row 1".length);
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.focus();
        }

        function DeleteItemFromList(itemIndex)
        {
            $("#content-edit-list-item-"+itemIndex).remove();
        }

        function AddItemToList(text, index = -1)
        {
            if (index == -1)
            {
                var itemCount = $("#content-edit-list-preview > div").length;
                index = itemCount;

            }
            if (text == null)
            {
                text = $("#content-edit-list-textbox-entry").val();
                $("#content-edit-list-textbox-entry").val("");
            }
            text = text.replace(/"/g, "&quot;");
            var html = `<div class="mb-3" id="content-edit-list-item-`+index+`">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-circle"></i></span>
                                <input type="text" class="form-control" id="content-edit-list-textbox-`+index+`" value="`+text+`">
                                <button class="btn btn-primary" type="button" onclick="DeleteItemFromList(`+index+`)"><i class="fa-regular fa-trash-can"></i></button>

                            </div>
                        </div>`;

            var existing = $("#content-edit-list-preview").html();

            
            $("#content-edit-list-preview").html(existing + html);
        }

        function OpenEditModal_Title(contentIndex) 
        {

            var contentToEdit = pageContent[contentIndex];
            
            $("#content-edit-title-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditTitleSaveButton").attr("onclick","SaveTitleModal("+contentIndex+")");
            $("#modalEditTitle").modal("show");
        }

        function OpenEditModal_Subtitle(contentIndex) 
        {

            var contentToEdit = pageContent[contentIndex];
            
            $("#content-edit-subtitle-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditSubtitleSaveButton").attr("onclick","SaveSubtitleModal("+contentIndex+")");
            $("#modalEditSubtitle").modal("show");
        }

        
        function OpenEditModal_Header(contentIndex)
        {

            var contentToEdit = pageContent[contentIndex];

            $("#content-edit-header-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditHeaderSaveButton").attr("onclick","SaveHeaderModal("+contentIndex+")");
            $("#modalEditHeader").modal("show");
        }

        function OpenEditModal_Button(contentIndex)
        {
            var contentToEdit = pageContent[contentIndex];
            var buttonLabel = GetContentElementData(contentToEdit, "data", 0);
            var buttonUrl = GetContentElementData(contentToEdit, "data", 1);

            $("#content-edit-button-label").val(buttonLabel);
            $("#content-edit-button-url").val(buttonUrl);
            $("#modalEditButtonSaveButton").attr("onclick","SaveButtonModal("+contentIndex+")");
            $("#modalEditButton").modal("show");
        }

        function OpenEditModal_Paragraph(contentIndex)
        {
            var contentToEdit = pageContent[contentIndex];

            $("#content-edit-paragraph-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditParagraphSaveButton").attr("onclick","SaveParagraphModal("+contentIndex+")");
            $("#modalEditParagraph").modal("show");
        }

        function OpenEditModal_Markdown(contentIndex)
        {
            var contentToEdit = pageContent[contentIndex];

            $("#content-edit-markdown-textbox").val(GetContentElementData(contentToEdit));
            SetMarkdownEditorView('code');
            UpdateMarkdownModalPreview();
            $("#modalEditMarkdownSaveButton").attr("onclick","SaveMarkdownModal("+contentIndex+")");
            $("#modalEditMarkdown").modal("show");
        }

        function OpenEditModal_List(contentIndex)
        {

            var contentToEdit = pageContent[contentIndex];
            var items = contentToEdit.data_items;

            console.log(items);

            var html = "";
            $("#content-edit-list-preview").html(html);

            for (let index = 0; index < items.length; index++) {
                const element = items[index];
                if (!element.deleted)
                    AddItemToList(GetContentElementData(contentToEdit, "data", index), index);
            }

            $("#modalEditListSaveButton").attr("onclick","SaveListModal("+contentIndex+")");
            $("#modalEditList").modal("show");
        }

        function OpenEditModal_Media(contentIndex) 
        {
            var contentToEdit = pageContent[contentIndex];
            
            //content-edit-media-textbox
            $("#content-edit-media-textbox").val(GetContentElementData(contentToEdit));
            $("#content-edit-media-image").attr("src","/assets/media/"+GetContentElementData(contentToEdit, "image_path"));
            $("#content-edit-media-id").val(GetContentElementData(contentToEdit, "media_id"));
            $("#modalEditMediaSaveButton").attr("onclick","SaveMediaModal("+contentIndex+")");
            $("#modalEditMedia").modal("show");
        }

        function OpenEditModal_Youtube(contentIndex) 
        {

            var contentToEdit = pageContent[contentIndex];
            
            $("#content-edit-youtube-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditYoutubeSaveButton").attr("onclick","SaveYoutubeModal("+contentIndex+")");
            $("#modalEditYoutube").modal("show");
            UpdateYoutubeModalPreview();
        }
 
        function OpenEditModal_SketchFab(contentIndex) 
        {
            var contentToEdit = pageContent[contentIndex];
            
            $("#content-edit-sketchfab-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditSketchFabSaveButton").attr("onclick","SaveSketchFabModal("+contentIndex+")");
            $("#modalEditSketchFab").modal("show");
            UpdateSketchFabModalPreview();
        }

        function OpenEditModal_Code(contentIndex) 
        {
            var contentToEdit = pageContent[contentIndex];
            
            $("#content-edit-code-textbox").val(GetContentElementData(contentToEdit));
            $("#modalEditCodeSaveButton").attr("onclick","SaveCodeModal("+contentIndex+")");
            $("#modalEditCode").modal("show");
        }

        function OpenEditModal_Slider(contentIndex) 
        {
            var contentToEdit = pageContent[contentIndex];
            $("#modalEditSlider").modal("show");
        }



    function OpenEditModal(index) {

        var contentToEdit = pageContent[index];

        const contentElementType = contentToEdit["content_type"];
        const contentElementTypeName = contentToEdit["content_type_name"] ?? "";
        console.log(contentToEdit);
        switch (contentElementType) {
            case 1:
                OpenEditModal_Title(index);
                break;

            case 2:
                OpenEditModal_Subtitle(index);
                break;

            case 3:
                OpenEditModal_Paragraph(index);
                break;

            case 4:
                OpenEditModal_List(index);
                break;

            case 5:
                OpenEditModal_Media(index);
                break;

            case 6:
                OpenEditModal_Youtube(index);
                break;

            case 7:
                OpenEditModal_Slider(index);
                break;

            case 8:
                OpenEditModal_SketchFab(index);
                break;

            case 9:
                break;

            case 10:
                OpenEditModal_Code(index);
                break;

            case 11:
                OpenEditModal_Header(index);
                break;
            case 12:
                OpenEditModal_Button(index);
                break;
            case 13:
                OpenEditModal_Markdown(index);
                break;

            default:
                
                console.error(`Error with element: ${JSON.stringify(contentToEdit)}`);
                break;
        }
    }
    var newElementInsertIndex = -1;
    function OpenNewElementModal(insertIndex = -1)
    {
        newElementInsertIndex = insertIndex;
        $("#modalNewElement").modal("show");
    }

    function AddInsertNewElement() {
        var insertIndex = newElementInsertIndex;
        // Get the value and convert it to an integer
        var typeValue = parseInt($("#content-new-element-select").val(), 10);
        
        // Get the text of the selected option
        var typeText = $("#content-new-element-select option:selected").text();
        
        var element = {
            content_id: <?= $_vPageContent->crand; ?>,
            content_detail_id: null,
            content_type: typeValue,
            content_type_name: typeText,
            element_order: 0,
            data_items: [],
            inserted: true
        };

        // You might also want to do something with the 'element' object after creating it
        if (insertIndex < 0)
        {

            pageContent.push(element);
        }
        else{
        pageContent.splice(insertIndex, 0, element);

        }

        UpdatePageContent(pageContent);

        var indexInsertedAt = (insertIndex >= 0) ? insertIndex : pageContent.length - 1;

        $("#modalNewElement").modal("hide");
        OpenEditModal(indexInsertedAt);
    }


    function ContentHasEdit(index) {
        
        var contentToEdit = pageContent[index];

        const contentElementType = contentToEdit["content_type"];

        switch (contentElementType) {
            case 9:
                return false;
                break;
        
            default:
                return true;
                break;
        }
    }

    function MoveContentElementUp(index) {
        if (index <= 0) {
            // If the element is already at the top, it can't be moved up.
            return;
        }
        const tmp = pageContent[index];
        tmp.updated = true;
        pageContent[index] = pageContent[index - 1];
        pageContent[index - 1] = tmp;
        UpdatePageContent(pageContent);
    }

    function MoveContentElementDown(index) {
        if (index >= pageContent.length - 1) {
            // If the element is already at the bottom, it can't be moved down.
            return;
        }
        const tmp = pageContent[index];
        tmp.updated = true;
        pageContent[index] = pageContent[index + 1];
        pageContent[index + 1] = tmp;
        UpdatePageContent(pageContent);
    }
    <?php } ?>
    function UpdatePageContent(contentData) {
        if (contentData == null)
            return;
        
        console.log(contentData);

        let html = "";
        var orderIndex = 0;
        contentData.forEach((contentElement, index) => {
            const contentElementType = contentElement["content_type"];
            const contentElementDataItems = contentElement["data_items"];
            if (!contentElement.deleted) {
                if (contentElement.element_order != orderIndex)
                {
                    contentElement.updated = true;
                }
                contentElement.element_order = orderIndex;
            <?php if ($_vPageContentEditMode) { ?>

                var editButton = "";
                if (ContentHasEdit(index))
                {
                    editButton = `<button type="button" class="btn btn-primary" onclick="OpenEditModal(`+index+`)"><i class="fa-solid fa-pen-to-square"></i></button>`;
                }

                var upButton = "";
                if (index > 0)
                {
                    upButton = `<button type="button" class="btn btn-primary" onclick="MoveContentElementUp(`+index+`)"><i class="fa-solid fa-arrow-up"></i></button>`;
                }

                var downButton = "";

                if (index < pageContent.length-1)
                {
                    downButton = `<button type="button" class="btn btn-primary" onclick="MoveContentElementDown(`+index+`)"><i class="fa-solid fa-arrow-down"></i></button>`;
                }
                html += `
                    <div class="card mb-2">
                        <div class="card-header">
                            <span class="card-title" style="font-size:1.5em;">${contentElement["content_type_name"]}</span>
                            <div class="btn-group float-end" role="group" aria-label="Basic example">
                                `+upButton+`
                                `+downButton+`
                                `+editButton+`
                                <button type="button" class="btn btn-primary" onclick="OpenNewElementModal(`+index+`)"><i class="fa-solid fa-plus"></i></button>
                                <button type="button" class="btn btn-danger" onclick="DeleteContentElement(`+index+`)"><i class="fa-regular fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                `;
        <?php } ?>

            html += renderContentElement(contentElement, index);
            orderIndex ++;
            }
            <?php if ($_vPageContentEditMode) { ?>
                html += `</div></div>`;
        <?php } ?>
        });

        document.getElementById('contentContainer').innerHTML = html;
        PR.prettyPrint();

        $("#edit-content-content-data").val(JSON.stringify(pageContent));
    }
    function DeleteContentElement(contentElementIndex) {
        
        pageContent[contentElementIndex].deleted = true;
        pageContent[contentElementIndex].data_items = [];
        UpdatePageContent(pageContent);
    }
    function SetContentElementDataValue(contentElementIndex, value, dataName = "data", dataIndex = 0) {

        // Ensure contentElement at the given index exists
        if (!pageContent[contentElementIndex]) {
            pageContent[contentElementIndex] = {};
        }

        pageContent[contentElementIndex].updated = true;

        // Ensure data_items array exists for the given contentElement
        if (!pageContent[contentElementIndex].data_items) {
            pageContent[contentElementIndex].data_items = [];
        }

        // Ensure data object at the given dataIndex exists within data_items array
        if (!pageContent[contentElementIndex].data_items[dataIndex]) {
            pageContent[contentElementIndex].data_items[dataIndex] = {};
        }

        // Set the value for the given dataName
        pageContent[contentElementIndex].data_items[dataIndex][dataName] = value;
        pageContent[contentElementIndex].data_items[dataIndex].data_order = dataIndex;
    }

    function GetContentElementData(contentElement, dataName = "data", index = 0){

        const contentElementDataItems = contentElement["data_items"];
        if (Array.isArray(contentElementDataItems) && index >= 0 && index < contentElementDataItems.length) {
            const dataItem = contentElementDataItems[index];
            if (dataItem && dataName in dataItem) {
                dataItem.data_order = index;
                return dataItem[dataName];
            }
        }

        return "";  // Or any other default value or error message
    }

    function renderContentElement(contentElement, i) {
        const contentElementType = contentElement["content_type"];
        const contentElementDataItems = contentElement["data_items"];
        let htmlContent = '';

        switch (contentElementType) {
            case 1:
                htmlContent = `<h1 class="display-3">${GetContentElementData(contentElement)}</h1>`;
                break;
            case 2:
                htmlContent = `<h1 class="display-5">${GetContentElementData(contentElement)}</h1>`;
                break;
            case 3:
                htmlContent = `<p>${GetContentElementData(contentElement)}</p>`;
                break;
            case 4:
                htmlContent = '<ul>';
                var order = 0;
                for (let j = 0; j < contentElementDataItems.length; j++) {
                    if (!contentElement.data_items[j].deleted)
                    {
                        contentElement.data_items[j].data_order = order;
                        htmlContent += `<li>${GetContentElementData(contentElement, "data", j)}</li>`;
                        order ++;
                    }
                }

                htmlContent += '</ul>';
                break;
            case 5:
                htmlContent = `
                <figure class="figure">
                    <img src="/assets/media/${GetContentElementData(contentElement, "image_path")}" class="figure-img img-fluid rounded">
                    <figcaption class="figure-caption">${GetContentElementData(contentElement, "data")}</figcaption>
                </figure>`;
                break;
            case 6:
                htmlContent = `
                <div class="ratio ratio-16x9">
                    <iframe src="https://www.youtube.com/embed/${GetContentElementData(contentElement)}?rel=0" title="YouTube video" allowfullscreen></iframe>
                </div>`;
                break;
            case 7:
                const carouselId = `content-carousel-${i}`;
                htmlContent = generateCarousel(contentElement);
                break;
            case 8:
                htmlContent = `
                <iframe title="SKETCH FAB"
                    style="width:100%;height:512px;"
                    frameborder="0" allowfullscreen
                    mozallowfullscreen="true"
                    webkitallowfullscreen="true"
                    src="https://sketchfab.com/models/${GetContentElementData(contentElement)}/embed">
                </iframe>`;
                break;
            case 9:
                htmlContent = '<hr class="border border-primary border-2 opacity-50">';
                break;
            case 10:
                htmlContent = `<pre class="prettyprint linenums"><code>${escapeHtml(GetContentElementData(contentElement))}</code></pre>`;
                break;

            case 11:
                htmlContent = `<h2 class="display-6">${GetContentElementData(contentElement)}</h2>`;
                break;

            case 12:
                const rawButtonLabel = GetContentElementData(contentElement, "data", 0) || "";
                const rawButtonUrl = GetContentElementData(contentElement, "data", 1) || "";
                const buttonLabel = escapeHtml(rawButtonLabel.trim() !== "" ? rawButtonLabel : "Button");
                const buttonUrl = escapeHtml(rawButtonUrl.trim() !== "" ? rawButtonUrl : "#");
                htmlContent = `<a class="btn btn-primary" href="${buttonUrl}">${buttonLabel}</a>`;
                break;

            case 13:
                htmlContent = `<div class="markdown-content">${renderMarkdownToHtml(GetContentElementData(contentElement))}</div>`;
                break;

            default:
                
                htmlContent = `<p>Error with element: ${JSON.stringify(contentElement)}</p>`;
                break;
        }

        return htmlContent;
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderMarkdownToHtml(markdownText) {
        if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined' && typeof document !== 'undefined') {
            const rawHtml = marked.parse(markdownText ?? '');
            const sanitizedFragment = DOMPurify.sanitize(rawHtml, { RETURN_DOM_FRAGMENT: true });
            const container = document.createElement('div');
            container.appendChild(sanitizedFragment);

            enhanceMarkdownContent(container);

            return container.innerHTML;
        }

        return escapeHtml(markdownText ?? '');
    }

    function enhanceMarkdownContent(container) {
        if (!container || typeof container.querySelectorAll !== 'function') {
            return;
        }

        const blockquotes = container.querySelectorAll('blockquote');
        blockquotes.forEach((blockquote) => {
            blockquote.classList.add('markdown-blockquote', 'border-start', 'ps-3', 'py-2', 'my-3');
        });

        const codeBlocks = container.querySelectorAll('pre > code');
        codeBlocks.forEach((code) => {
            const pre = code.parentElement;
            if (!pre || !pre.parentNode) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'markdown-code-block position-relative';

            pre.parentNode.insertBefore(wrapper, pre);

            const codeWrapper = document.createElement('div');
            codeWrapper.className = 'markdown-code-wrapper';
            wrapper.appendChild(codeWrapper);

            const lineNumberColumn = document.createElement('div');
            lineNumberColumn.className = 'markdown-line-numbers';
            codeWrapper.appendChild(lineNumberColumn);

            const scrollContainer = document.createElement('div');
            scrollContainer.className = 'markdown-code-scroll';
            codeWrapper.appendChild(scrollContainer);
            scrollContainer.appendChild(pre);

            pre.classList.add('markdown-pre');
            code.classList.add('markdown-code');

            const codeText = code.textContent || '';
            const normalizedCode = codeText.replace(/\r\n?/g, '\n');
            const codeLines = normalizedCode.split('\n');
            if (normalizedCode.endsWith('\n')) {
                codeLines.push('');
            }

            code.innerHTML = '';
            codeLines.forEach((line, index) => {
                const lineNumber = document.createElement('span');
                lineNumber.className = 'markdown-line-number';
                lineNumber.textContent = String(index + 1);
                lineNumberColumn.appendChild(lineNumber);

                const lineSpan = document.createElement('span');
                lineSpan.className = 'markdown-code-line';
                lineSpan.textContent = line === '' ? '\u200B' : line;
                code.appendChild(lineSpan);
            });

            const copyButton = document.createElement('button');
            copyButton.type = 'button';
            copyButton.className = 'btn btn-sm btn-outline-secondary markdown-copy-button';
            copyButton.setAttribute('aria-label', 'Copy code');
            copyButton.setAttribute('title', 'Copy code');
            copyButton.setAttribute('data-code-text', codeText);
            copyButton.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i><span class="visually-hidden">Copy code</span>';

            wrapper.appendChild(copyButton);
        });

        const inlineCodes = container.querySelectorAll('p > code, li > code');
        inlineCodes.forEach((inlineCode) => {
            inlineCode.classList.add('markdown-inline-code');
        });
    }

    $(document).on('click', '.markdown-copy-button', function () {
        const button = $(this);
        const preElement = button.siblings('pre')[0];
        const codeElement = preElement ? preElement.querySelector('code') : null;
        const codeText = button.attr('data-code-text') || (codeElement ? codeElement.innerText : '');

        if (!codeText) {
            return;
        }

        const originalLabel = button.data('original-label') || button.html();
        const originalAria = button.data('original-aria') || button.attr('aria-label');
        const originalTitle = button.data('original-title') || button.attr('title');
        button.data('original-label', originalLabel);
        button.data('original-aria', originalAria);
        button.data('original-title', originalTitle);

        const showFeedback = (text, stateClass) => {
            const iconClass = stateClass === 'copy-error' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-check';
            const iconHtml = '<i class="' + iconClass + '" aria-hidden="true"></i><span class="visually-hidden">' + text + '</span>';
            button.html(iconHtml);
            button.attr('aria-label', text);
            button.attr('title', text);
            button.removeClass('copy-success copy-error');
            if (stateClass) {
                button.addClass(stateClass);
            }

            setTimeout(() => {
                button.html(button.data('original-label'));
                button.removeClass('copy-success copy-error');
                button.attr('aria-label', button.data('original-aria') || 'Copy code');
                button.attr('title', button.data('original-title') || 'Copy code');
            }, 2000);
        };

        const fallbackCopy = () => {
            const textarea = document.createElement('textarea');
            textarea.value = codeText;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            let success = false;

            try {
                success = document.execCommand('copy');
            } catch (err) {
                success = false;
            }

            document.body.removeChild(textarea);

            return success;
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
                .writeText(codeText)
                .then(() => {
                    showFeedback('Copied!', 'copy-success');
                })
                .catch(() => {
                    if (fallbackCopy()) {
                        showFeedback('Copied!', 'copy-success');
                    } else {
                        showFeedback('Copy failed', 'copy-error');
                    }
                });
        } else {
            if (fallbackCopy()) {
                showFeedback('Copied!', 'copy-success');
            } else {
                showFeedback('Copy failed', 'copy-error');
            }
        }
    });


    function generateCarousel(data) {
        const carouselId = `content-carousel-${data.content_detail_id}`;
        
        let indicators = '';
        let slides = '';
        for (let index = 0; index < data.data_items.length; index++) {
            //const item = data.data_items[index];
            indicators += `<button type="button" data-bs-target="#${carouselId}" data-bs-slide-to="${index}" ${index === 0 ? 'class="active" aria-current="true"' : ''} aria-label="${GetContentElementData(data, "data", index)}"></button>`;
            
            slides += `
            <div class="carousel-item ${index === 0 ? 'active' : ''}" data-bs-interval="7000">
                <img src="/assets/media/${GetContentElementData(data, "image_path", index)}" class="d-block w-100">
                <div class="carousel-caption d-block d-md-block text-shadow">
                    <h5>${GetContentElementData(data, "data", index)}</h5>
                    <p></p>  <!-- Placeholder for description if you add it later -->
                </div>
            </div>`;
        }
        

        return `<div id="${carouselId}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        ${indicators}
                    </div>
                    <div class="carousel-inner">
                        ${slides}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>`;
    }

</script>
<?php

unset($_vPageContent);

}
?>
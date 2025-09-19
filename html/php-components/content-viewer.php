<?php 

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Controllers\ContentController;
use Kickback\Services\Session;
use Kickback\Common\Utility\FormToken;

if (!isset($_vPageContentEditMode))
{
    $_vPageContentEditMode = false;
}
if (!isset($_vPageContent))
{
  throw new \Exception("No page content for content viewer!");
}

$_vContentViewerEditorTitle = (Session::isLoggedIn() ? (isset($_vContentViewerEditorTitle) ? $_vContentViewerEditorTitle : "Welcome back, ".Session::getCurrentAccount()->username.". What would you like to do?") : "");
$_vCanEditContent = (isset($_vCanEditContent) && $_vCanEditContent == true);
$_vPageContentEditMode = (isset($_POST["edit-content"]) && $_vCanEditContent);
if ($_vCanEditContent)
{

    if ($_vPageContentEditMode) {
        $contentTypes = ContentController::queryContentTypes();
?>

<!-- EDIT CODE MODAL -->
<div class="modal modal-lg fade" id="modalEditCode" tabindex="-1" aria-labelledby="modalEditCodeLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditCodeLabel">Edit Code Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="content-edit-code-textbox" class="form-label">Enter code</label>
        <textarea class="form-control" id="content-edit-code-textbox" rows="10"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditCodeSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- NEW ELEMENT MODAL -->
<div class="modal modal-lg fade" id="modalNewElement" tabindex="-1" aria-labelledby="modalNewElementLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalNewElementLabel">What element do you want to add?</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <div class="input-group">
            <select class="form-select" id="content-new-element-select">
            <option value="" selected>Select an element type</option>
            <?php
                foreach($contentTypes as $type) {
                    echo "<option value='{$type["Id"]}'>{$type["type_name"]}</option>";
                }
            ?>
            </select>
            <button class="btn bg-ranked-1" type="button" onclick="AddInsertNewElement()">Add Element</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT TITLE MODAL -->
<div class="modal modal-lg fade" id="modalEditTitle" tabindex="-1" aria-labelledby="modalEditTitleLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditTitleLabel">Edit Title Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="content-edit-title-textbox" class="form-label">Enter a Title</label>
        <input class="form-control form-control-lg" type="text" id="content-edit-title-textbox">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditTitleSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT SUBTITLE MODAL -->
<div class="modal modal-lg fade" id="modalEditSubtitle" tabindex="-1" aria-labelledby="modalEditSubtitleLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditSubtitleLabel">Edit Subtitle Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="content-edit-subtitle-textbox" class="form-label">Enter a Subtitle</label>
        <input class="form-control form-control-lg" type="text" id="content-edit-subtitle-textbox">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditSubtitleSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>


<!-- EDIT HEADER MODAL -->
<div class="modal modal-lg fade" id="modalEditHeader" tabindex="-1" aria-labelledby="modalEditHeaderLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditHeaderLabel">Edit Header Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="content-edit-header-textbox" class="form-label">Enter a Header</label>
        <input class="form-control form-control-lg" type="text" id="content-edit-header-textbox">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditHeaderSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT BUTTON MODAL -->
<div class="modal modal-lg fade" id="modalEditButton" tabindex="-1" aria-labelledby="modalEditButtonLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditButtonLabel">Edit Button Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="content-edit-button-label" class="form-label">Button Label</label>
          <input class="form-control" type="text" id="content-edit-button-label">
        </div>
        <div class="mb-3">
          <label for="content-edit-button-url" class="form-label">Link URL</label>
          <input class="form-control" type="url" id="content-edit-button-url" placeholder="https://example.com">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditButtonSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT PARAGRAPH MODAL -->
<div class="modal modal-lg fade" id="modalEditParagraph" tabindex="-1" aria-labelledby="modalEditParagraphLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditParagraphLabel">Edit Paragraph Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="content-edit-paragraph-textbox" class="form-label">Enter a Paragraph</label>
        <textarea class="form-control" id="content-edit-paragraph-textbox" rows="10"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditParagraphSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT MARKDOWN MODAL -->
<div class="modal modal-lg fade" id="modalEditMarkdown" tabindex="-1" aria-labelledby="modalEditMarkdownLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modalEditMarkdownLabel">Edit Markdown Content Element</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-column gap-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <label for="content-edit-markdown-textbox" class="form-label mb-0">Markdown Editor</label>
            <div class="btn-group btn-group-sm" role="group" aria-label="Markdown view toggle">
              <button type="button" class="btn btn-outline-secondary active" id="markdown-view-toggle-code" onclick="SetMarkdownEditorView('code')">
                <i class="fa-solid fa-code"></i> Markdown
              </button>
              <button type="button" class="btn btn-outline-secondary" id="markdown-view-toggle-preview" onclick="SetMarkdownEditorView('preview')">
                <i class="fa-solid fa-eye"></i> Preview
              </button>
            </div>
          </div>
          <div class="btn-toolbar flex-wrap" id="content-edit-markdown-toolbar" role="toolbar" aria-label="Markdown toolbar">
            <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Text formatting">
              <button type="button" class="btn btn-outline-secondary" title="Bold" onclick="ApplyMarkdownWrap('**','**','bold text')"><i class="fa-solid fa-bold"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Italic" onclick="ApplyMarkdownWrap('*','*','italic text')"><i class="fa-solid fa-italic"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Strikethrough" onclick="ApplyMarkdownWrap('~~','~~','strikethrough')"><i class="fa-solid fa-strikethrough"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Inline code" onclick="ApplyMarkdownWrap('`','`','code')"><i class="fa-solid fa-terminal"></i></button>
            </div>
            <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Headings">
              <button type="button" class="btn btn-outline-secondary" title="Heading 1" onclick="ApplyMarkdownHeading(1)">H1</button>
              <button type="button" class="btn btn-outline-secondary" title="Heading 2" onclick="ApplyMarkdownHeading(2)">H2</button>
              <button type="button" class="btn btn-outline-secondary" title="Heading 3" onclick="ApplyMarkdownHeading(3)">H3</button>
            </div>
            <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Blocks">
              <button type="button" class="btn btn-outline-secondary" title="Blockquote" onclick="ApplyMarkdownPrefix('> ')"><i class="fa-solid fa-quote-left"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Code block" onclick="ApplyMarkdownBlock('```\n','\n```','code block')"><i class="fa-solid fa-code"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Horizontal rule" onclick="InsertMarkdownHorizontalRule()"><i class="fa-solid fa-grip-lines"></i></button>
            </div>
            <div class="btn-group btn-group-sm me-2 mb-2" role="group" aria-label="Lists">
              <button type="button" class="btn btn-outline-secondary" title="Bulleted list" onclick="ApplyMarkdownList('unordered')"><i class="fa-solid fa-list-ul"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Numbered list" onclick="ApplyMarkdownList('ordered')"><i class="fa-solid fa-list-ol"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Task list" onclick="ApplyMarkdownList('task')"><i class="fa-regular fa-square-check"></i></button>
            </div>
            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Links and media">
              <button type="button" class="btn btn-outline-secondary" title="Link" onclick="InsertMarkdownLink()"><i class="fa-solid fa-link"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Image" onclick="InsertMarkdownImage()"><i class="fa-regular fa-image"></i></button>
              <button type="button" class="btn btn-outline-secondary" title="Table" onclick="InsertMarkdownTable()"><i class="fa-solid fa-table"></i></button>
            </div>
          </div>
          <textarea class="form-control" id="content-edit-markdown-textbox" rows="12" oninput="HandleMarkdownEditorInput()"></textarea>
          <div id="content-edit-markdown-preview" class="border rounded p-3 bg-body-tertiary d-none"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn bg-ranked-1" id="modalEditMarkdownSaveButton" onclick="">Apply changes</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT LIST MODAL -->
<div class="modal modal-lg fade" id="modalEditList" tabindex="-1" aria-labelledby="modalEditListLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalEditListLabel">Edit List Content Element</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="row">
                    <h3 class="">List:</h3>
                    <div id="content-edit-list-preview">
                        
                    </div>
                </div>
                <div class="row">
                    
                    <div class="mb-3">
                        <hr/>
                        <div class="input-group">
                            <input type="text" class="form-control" id="content-edit-list-textbox-entry" >
                            <button class="btn bg-ranked-1" type="button" onclick="AddItemToList()"><i class="fa-solid fa-plus"></i></button>

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" id="modalEditListSaveButton" onclick="">Apply changes</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MEDIA MODAL -->
<div class="modal modal-lg fade" id="modalEditMedia" tabindex="-1" aria-labelledby="modalEditMediaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalEditMediaLabel">Edit Media Content Element</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="row mb-3">
                    <div class="col-12">
                            <button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditMedia','content-edit-media-image','content-edit-media-id')">Select Media</button>
                    </div>
                </div>
            
                <div class="row mb-3">
                        <div class="col-12">
                                <input type="hidden" id="content-edit-media-id">
                                <img src="/assets/media/" class="figure-img img-fluid rounded" style="background-color: black; max-width: 100%; width: auto; height: auto;" id="content-edit-media-image">

                        </div>
                    </div>
                    <div class="row mb-3">
                        
                        <div class="col-12">
                            <label for="content-edit-media-textbox" class="form-label">Media Caption</label>
                            <input type="text" class="form-control" id="content-edit-media-textbox">
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" id="modalEditMediaSaveButton" >Apply changes</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT YOUTUBE MODAL -->
<div class="modal modal-lg fade" id="modalEditYoutube" tabindex="-1" aria-labelledby="modalEditYoutubeLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalEditYoutubeLabel">Edit Youtube Content Element</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    
                    <div class="mb-3">
                    <label for="content-edit-youtube-textbox" class="form-label">Enter a Youtube Video ID</label>
                    <div class="input-group">
                        <span class="input-group-text">https://www.youtube.com/watch?v=</span>
                        <input type="text" class="form-control" id="content-edit-youtube-textbox" onchange="UpdateYoutubeModalPreview()">
                        <button class="btn btn-primary" type="button" onclick="UpdateYoutubeModalPreview()">Load Preview</button>

                    </div>
                    <div class="form-text" id="basic-addon4">Just the ID of the video (example: "kywr54C369w")</div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <h6>Video Preview</h6>
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.youtube.com/embed/?rel=0" title="YouTube video" allowfullscreen id="content-edit-youtube-preview-iframe"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" id="modalEditYoutubeSaveButton" onclick="">Apply changes</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SKETCH FAB MODAL -->
<div class="modal modal-lg fade" id="modalEditSketchFab" tabindex="-1" aria-labelledby="modalEditSketchFabLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalEditSketchFabLabel">Edit Sketch Fab Content Element</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
            
                <div class="row">
                        
                        <div class="mb-3">
                        <label for="content-edit-sketchfab-textbox" class="form-label">Enter a Sketch Fab ID</label>
                        <div class="input-group">
                            <span class="input-group-text">https://sketchfab.com/models/</span>
                            <input type="text" class="form-control" id="content-edit-sketchfab-textbox" onchange="UpdateSketchFabModalPreview()">
                            <span class="input-group-text">/embed</span>
                            <button class="btn btn-primary" type="button" onclick="UpdateSketchFabModalPreview()">Load Preview</button>

                        </div>
                        <div class="form-text" id="basic-addon4">Just the ID of the model (example: "5d8f30c391874f20a22f070720ef3c3c")</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>Sketch Fab Preview</h6>
                            <div class="ratio ratio-16x9">
                            <iframe title="SKETCH FAB" id="content-edit-sketchfab-preview-iframe"
                                style="width:100%;height:100%;"
                                frameborder="0" allowfullscreen
                                mozallowfullscreen="true"
                                webkitallowfullscreen="true"
                                src="https://sketchfab.com/models/5d8f30c391874f20a22f070720ef3c3c/embed">
                            </iframe>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" id="modalEditSketchFabSaveButton" onclick="">Apply changes</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SLIDER MODAL -->
<div class="modal modal-lg fade" id="modalEditSlider" tabindex="-1" aria-labelledby="modalEditSliderLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalEditSliderLabel">Edit Slider Content Element</h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="btn-group flex-wrap" id="slider-slide-buttons" role="group" aria-label="Slides"></div>
                            <button type="button" class="btn btn-primary" id="modalEditSliderAddButton" aria-label="Add slide">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <div class="ms-auto d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-primary" id="modalEditSliderSelectMediaButton">
                                    <i class="fa-solid fa-image me-1"></i> Select Media
                                </button>
                                <button type="button" class="btn btn-danger" id="modalEditSliderDeleteButton">Delete Slide</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="ratio ratio-16x9 border rounded bg-black overflow-hidden d-flex align-items-center justify-content-center">
                            <img src="/assets/media/items/placeholder.png" class="figure-img img-fluid rounded" id="content-edit-slider-image" alt="Slider preview">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="content-edit-slide-textbox" class="form-label">Slide Title</label>
                        <input type="text" class="form-control" id="content-edit-slide-textbox">
                        <input type="hidden" id="content-edit-slider-media-id">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="content-edit-slide-subtext" class="form-label">Slide Subtitle</label>
                        <input type="text" class="form-control" id="content-edit-slide-subtext">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="content-edit-slider-auto-slide" checked>
                            <label class="form-check-label" for="content-edit-slider-auto-slide">Auto Slide</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn bg-ranked-1" id="modalEditSliderSaveButton">Apply changes</button>
            </div>
        </div>
    </div>
</div>



<?php } ?>

<div class="card mb-3">
    
    <div class="card-header bg-ranked-1">
        <h5 class="mb-0"><?php echo $_vContentViewerEditorTitle; ?></h5>
    </div>
    <div class="card-body">
        <?php if ($_vPageContentEditMode) { ?>

            <form method="POST">
                <?= FormToken::registerForm(); ?>

                <input type="hidden" name="edit-content-container-type" value="<?= $_vPageContent->containerType; ?>"/>
                <input type="hidden" name="edit-content-container-id" value="<?= $_vPageContent->containerId; ?>"/>
                <input type="hidden" name="edit-content-content-data" id="edit-content-content-data" value=""/>
                <a class="btn btn-primary" href="" >Cancel</a>
                <input type="submit" class="btn btn-primary" name="save-content" value="Save"/>
            </form>
        <?php } else { ?>
            <form method="POST">
                <input type="hidden" name="edit-content-container-type" value="<?= $_vPageContent->containerType; ?>"/>
                <input type="hidden" name="edit-content-container-id" value="<?= $_vPageContent->containerId; ?>"/>
                <input type="submit" class="btn btn-primary" name="edit-content" value="Edit Content"/>
            </form>
        <?php } ?>
    </div>
</div>

<?php
}
?>



<div class="row">
    <div class="col-12" id="contentContainer">
    </div>
    <?php 

if ($_vCanEditContent)
{

    if ($_vPageContentEditMode) {
?>

    <div class="col-12">
        <div class="card text-center" style="border:none">
            
            <div class="card-body">
                <button type="button" class="btn btn-primary btn-lg" onclick="OpenNewElementModal()">Add New Element</a>
            </div>
        </div>
    </div>
    <?php 
    }
}
?>
</div>
<?php

//unset($_vPageContent);
//unset($_vCanEditContent);

?>

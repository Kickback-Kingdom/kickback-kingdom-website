<div class="input-group">
  <select class="form-select" onchange="OnSelectMediaChangeSearchParams()" id="selectMediaSearchDirectory">
    <option value="" selected>All</option>
    <?php

        foreach($mediaDirs as $dir) {
            echo "<option value='{$dir}'>{$dir}</option>";
        }
    ?>
  </select>  
  <input type="text" class="form-control" onchange="OnSelectMediaChangeSearchParams()" id="selectMediaSearchTerm">
  <button class="btn btn-primary" type="button" onclick="OnSelectMediaChangeSearchParams()">Search</button>

</div>
<div class="card mt-3">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <!-- side-bar colleps block stat-->
                <div id="pagination">
                    <!-- Pagination buttons will be inserted here via JS -->
                </div>
                <div class="inventory-grid inventory-grid-lg card mt-3" id="selectMediaSearchResults" style="border-style: none;">
                    
                </div>

            </div>
        </div> 
    </div>
</div>


<script type="module">
import { initPixelEditor } from '/assets/js/pixel-editor.js';

const promptTemplates = {
    "lich card art": desc => `Pixel art L.I.C.H. card of ${desc}, 16-bit style, consistent palette`,
    "dragon card art": desc => `Pixel art dragon card of ${desc}, 16-bit style, consistent palette`
};

let currentSelectMediaPage = 1; // default page
const itemsPerSelectMediaPage = 6; // or however many you want

var pixelEditorSettings = null;
var lastPixelEditorSrc = '';
var croppedImageData = '';
var pixelatedImageData = '';

let selectedPromptTemplate = '';


<?php if(Kickback\Services\Session::getCurrentAccount()->canUploadImages()) { ?>
let cropper;
let pixelEditor;
let mediaUploadStep = 1;

function PromptGenerateWithAI() {
    const editor = document.getElementById('aiPromptEditor');
    const btn = document.getElementById('btnGenerateWithAI');
    if (editor) {
        editor.classList.remove('d-none');
    }
    if (btn) {
        btn.classList.add('d-none');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const templateSelect = document.getElementById('imagePromptTemplate');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            selectedPromptTemplate = this.value;
        });
    }

    const modelSelect = document.getElementById('imageModel');
    const sizeSelect = document.getElementById('imageSize');
    if (modelSelect && sizeSelect) {
        const sizeOptions = {
            'gpt-image-1': [
                '1024x1024',
                '1024x1536',
                '1536x1024',
                'auto'
            ],
            'dall-e-2': [
                '1024x1024',
                '512x512',
                '256x256'
            ]
        };

        const renderSizeOptions = (model) => {
            sizeSelect.innerHTML = '';
            (sizeOptions[model] || []).forEach(value => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = value;
                sizeSelect.appendChild(opt);
            });
        };

        modelSelect.addEventListener('change', () => {
            renderSizeOptions(modelSelect.value);
        });

        renderSizeOptions(modelSelect.value);
    }
});

function GeneratePromptImage(prompt)
{
    pixelEditorSettings = null;
    lastPixelEditorSrc = '';
    croppedImageData = '';
    pixelatedImageData = '';

    ShowLoadingBar();

    const directoryEl = document.getElementById('mediaUploadImageFolderSelect');
    const nameEl = document.getElementById('mediaUploadImageNameTextbox');
    const descEl = document.getElementById('mediaUploadImageDescTextbox');
    const templateSelect = document.getElementById('imagePromptTemplate');
    const template = templateSelect ? templateSelect.value : '';
    const descriptionEl = document.getElementById('imagePromptDescription');
    const description = descriptionEl ? descriptionEl.value : '';
    const finalPrompt = promptTemplates[template]
        ? promptTemplates[template](description)
        : description;
    const sizeEl = document.getElementById('imageSize');
    const modelEl = document.getElementById('imageModel');
    const sessionToken = "<?php echo $_SESSION["sessionToken"]; ?>";

    const formData = new URLSearchParams();
    const resolvedPrompt = (typeof prompt === 'string' && prompt.length > 0)
        ? prompt
        : finalPrompt;
    formData.append('prompt', resolvedPrompt);
    if (directoryEl) { formData.append('directory', directoryEl.value); }
    if (nameEl) { formData.append('name', nameEl.value); }
    if (descEl) { formData.append('desc', descEl.value); }
    if (sizeEl && sizeEl.value) {
        formData.append('size', sizeEl.value);
    }
    if (modelEl && modelEl.value) {
        formData.append('model', modelEl.value);
    }
    formData.append('sessionToken', sessionToken);

    fetch('/api/v1/media/generate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(resp => resp.json())
    .then(data => {
        HideLoadingBar();
        const errEl = document.getElementById('aiGenerateError');
        if (data && data.success) {
            const generatedImage = data.data.imgBase64;
            croppedImageData = generatedImage;
            const img = document.getElementById('imageUploadPreview');
            if (img) {
                img.onload = function() { InitCropper(); };
                img.src = generatedImage;
            }
            mediaUploadStep = 2;
            if (typeof UpdateMediaUploadModal === 'function') {
                UpdateMediaUploadModal();
            }
            if (errEl) {
                errEl.classList.add('d-none');
                errEl.textContent = '';
            }
        } else {
            if (errEl) {
                errEl.textContent = (data && data.message) ? data.message : 'Generation failed';
                errEl.classList.remove('d-none');
            }
            console.error('Generation failed', data);
        }
    })
    .catch(err => {
        HideLoadingBar();
        const errEl = document.getElementById('aiGenerateError');
        if (errEl) {
            errEl.textContent = 'Generation error';
            errEl.classList.remove('d-none');
        }
        console.error('Generation error', err);
    });
}
function OpenMediaUploadModal()
{
    
    $("#selectMediaModal").modal("hide");
    $("#uploadMediaModal").modal("show");
}

function CloseMediaUploadModal()
{
    $("#uploadMediaModal").modal("hide");
}

function UpdateMediaUploadModal()
{
    $("#mediaUploadStep-1").removeClass("wizard-step-active");
    $("#mediaUploadStep-2").removeClass("wizard-step-active");
    $("#mediaUploadStep-3").removeClass("wizard-step-active");
    $("#mediaUploadStep-4").removeClass("wizard-step-active");

    $("#mediaUploadStep-1-link").removeClass("active");
    $("#mediaUploadStep-2-link").removeClass("active");
    $("#mediaUploadStep-3-link").removeClass("active");
    $("#mediaUploadStep-4-link").removeClass("active");

    $("#mediaUploadStep-1-pill").removeClass("bg-ranked-1");
    $("#mediaUploadStep-2-pill").removeClass("bg-ranked-1");
    $("#mediaUploadStep-3-pill").removeClass("bg-ranked-1");
    $("#mediaUploadStep-4-pill").removeClass("bg-ranked-1");

    $("#mediaUploadStep-1-pill").addClass("bg-primary");
    $("#mediaUploadStep-2-pill").addClass("bg-primary");
    $("#mediaUploadStep-3-pill").addClass("bg-primary");
    $("#mediaUploadStep-4-pill").addClass("bg-primary");

    $("#mediaUploadButtonNext").show();

    $("#mediaUploadStep-"+mediaUploadStep).addClass("wizard-step-active");
    $("#mediaUploadStep-"+mediaUploadStep+"-pill").addClass("bg-ranked-1");
    $("#mediaUploadStep-"+mediaUploadStep+"-link").addClass("active");

    if (mediaUploadStep == 1)
    {
        ResetMediaUploadWizardStep2();
        $("#mediaUploadButtonPrev").html("Cancel");
        $("#mediaUploadButtonNext").html("Next");
    }

    if (mediaUploadStep == 2)
    {

        $("#mediaUploadButtonPrev").html("Back");
        $("#mediaUploadButtonNext").html("Crop");

        const uploadPreview = document.getElementById('imageUploadPreview');
        if (uploadPreview && uploadPreview.src)
        {
            if (uploadPreview.complete)
            {
                InitCropper();
            }
            else
            {
                uploadPreview.addEventListener('load', InitCropper, { once: true });
            }
        }
    }

    if (mediaUploadStep == 3)
    {
        let container = document.getElementById('pixelEditor');
        const source = document.getElementById('imagePreviewEdited');
        if (croppedImageData) {
            source.src = croppedImageData;
        }
        let currentSrc = source.src;

        if (!croppedImageData || currentSrc !== lastPixelEditorSrc) {
            CropImageFromEditor();
            source.src = croppedImageData;
            currentSrc = source.src;
        }
        if (!pixelEditorSettings) {
            pixelEditorSettings = {
                pixelWidth: 64,
                method: 'neighbor',
                paletteSize: 16,
                dither: false,
                autoRender: true,
                autoFit: true,
                brightness: 0,
                contrast: 0,
                saturation: 100,
                enableTune: false,
                tune: {R:0, Y:0, G:0, C:0, B:0, M:0},
                enableRemap: false,
                remapStrength: 100,
                map: {R:'0', Y:'0', G:'0', C:'0', B:'0', M:'0'},
                mapStr: {R:100, Y:100, G:100, C:100, B:100, M:100}
            };
        }
        lastPixelEditorSrc = currentSrc;

        if (pixelEditor) {
            // Preserve current settings and clean up listeners from previous editor instance
            pixelEditorSettings = pixelEditor.getSettings();
            pixelEditor.destroy();
            const newContainer = container.cloneNode(true);
            container.parentNode.replaceChild(newContainer, container);
            container = newContainer;
            pixelEditor = null;
        }

        const initializeEditor = () => {
            pixelEditor = initPixelEditor(container, source, pixelEditorSettings);
        };

        if (source.complete) {
            initializeEditor();
        } else {
            source.addEventListener('load', initializeEditor, { once: true });
        }

        $("#mediaUploadButtonPrev").html("Back");
        $("#mediaUploadButtonNext").hide();
    }

    if (mediaUploadStep == 4)
    {
        let preview = document.getElementById('imagePreviewEdited');
        if (pixelatedImageData) {
            preview.src = pixelatedImageData;
        } else if (croppedImageData) {
            preview.src = croppedImageData;
        }
        $("#mediaUploadButtonPrev").html("Back");
        $("#mediaUploadButtonNext").html("Upload");
    }
}

function ResetMediaUploadWizardStep2()
{
    if (cropper) {
        cropper.destroy();
    }
    if (pixelEditor) {
        // Ensure any event listeners from the editor are removed
        pixelEditorSettings = pixelEditor.getSettings();
        pixelEditor.destroy();
        pixelEditor = null;
    }

    $("#mediaUploadUsageSelect").val("-1");
    document.getElementById('imagePreview').src = "";
}

function CropImageFromEditor()
{
    let imgElement = document.getElementById('imagePreviewEdited');

    // Convert the cropped canvas to a data URL
    let dataURL = cropper.getCroppedCanvas().toDataURL();

    // Set the data URL as the source of the image element
    imgElement.src = dataURL;
    croppedImageData = dataURL;
    pixelatedImageData = '';
}

function SkipPixelation()
{
    pixelatedImageData = croppedImageData;
    mediaUploadStep = 4;
    UpdateMediaUploadModal();
}

function ApplyPixelation()
{
    if (pixelEditor) {
        pixelEditor.render();
    }
    const canvas = document.getElementById('pixelCanvas');
    let imgElement = document.getElementById('imagePreviewEdited');
    pixelatedImageData = canvas.toDataURL();
    imgElement.src = pixelatedImageData;
    mediaUploadStep = 4;
    UpdateMediaUploadModal();
}

function MediaUploadNextStep()
{
    if (mediaUploadStep<4)
    {
        mediaUploadStep++;
    }
    else{
        //upload image
        UploadImageData();
    }
    UpdateMediaUploadModal();
}

function MediaUploadPrevStep()
{

    if (mediaUploadStep>1)
    {
        mediaUploadStep--;
    }
    if (mediaUploadStep === 2) {
        lastPixelEditorSrc = '';
        pixelatedImageData = '';
    }
    UpdateMediaUploadModal();
}

function GetAspectRatio()
{
    var ar = $("#mediaUploadUsageSelect").val();

    if (ar != -1)
    {
        var val = parseFloat(ar);
        if (val == 0)
            return null;

        return 1/val;
    }

    return null;
}

function InitCropper()
{
    const imageUploaded = document.getElementById('imageUploadPreview');
    const image = document.getElementById('imagePreview');
    image.src = imageUploaded.src;
    // If cropper instance already exists, destroy it
    if (cropper) {
        cropper.destroy();
    }
    
    cropper = new Cropper(image, {
        aspectRatio: GetAspectRatio(),  // You can set this to any desired aspect ratio or free crop
        zoomable: true,
        scalable: true,
        viewMode: 1,
    });
}

function OnPhotoUsageChanged(input)
{
    InitCropper();
}

function OnUploadFileChanged(input)
{
    pixelEditorSettings = null;
    lastPixelEditorSrc = '';
    croppedImageData = '';
    pixelatedImageData = '';
    const info = document.getElementById('aiPromptInfo');
    const errEl = document.getElementById('aiGenerateError');
    if (info) {
        info.classList.add('d-none');
    }
    if (errEl) {
        errEl.classList.add('d-none');
        errEl.textContent = '';
    }
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(event) {
            const image = document.getElementById('imageUploadPreview');
            image.src = event.target.result;

        }

        reader.readAsDataURL(file);
    }
}

function UploadImageData() {

    CloseMediaUploadModal();
    ShowLoadingBar();

    let imgElement = document.getElementById('imagePreviewEdited');
    let croppedDataURL = imgElement.src;

    let directory = document.getElementById('mediaUploadImageFolderSelect').value;
    let name = document.getElementById('mediaUploadImageNameTextbox').value;
    let desc = document.getElementById('mediaUploadImageDescTextbox').value;

    // Assuming the sessionToken is stored in a variable or you can get it similarly from another input field
    let sessionToken = "<?php echo $_SESSION["sessionToken"]; ?>";

    if (croppedDataURL) {
        let formData = new URLSearchParams();
        formData.append("imgBase64", croppedDataURL);
        formData.append("directory", directory);
        formData.append("name", name);
        formData.append("desc", desc);
        formData.append("sessionToken", sessionToken);

        fetch('/api/v1/media/upload.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Image uploaded!", data);
            HideLoadingBar();
            ReopenSelectMediaModal();
        })
        .catch(error => {
            console.error('Error:', error);
            HideLoadingBar();
            OpenMediaUploadModal();
        });
    }
}


<?php } ?>

var selectMediaModalCallerId = -1;
var selectMediaImageElementOutputId = -1;
var selectMediaImageFormInputId = -1;
var selectMediaCallbackFunction = null;

function ReopenSelectMediaModal()
{
    
    $("#selectMediaModal").modal("show");

    SearchForMedia();
}

function OpenSelectMediaModal(prevModal = null, imageElementOutputId = null, imageFormInputId = null, callbackFunction = null)
{
    selectMediaImageElementOutputId = imageElementOutputId;
    selectMediaImageFormInputId = imageFormInputId;
    selectMediaCallbackFunction = callbackFunction;

    if (prevModal != null)
    {
        $("#"+prevModal).modal("hide");
        selectMediaModalCallerId = prevModal;
    }
    ReopenSelectMediaModal();
}

function OnSelectMediaChangeSearchParams()
{
    console.log("search for media!");
    currentSelectMediaPage = 1;
    SearchForMedia();
}

function SearchForMedia()
{
    const data = {
        directory: $("#selectMediaSearchDirectory").val(),
        searchTerm: $("#selectMediaSearchTerm").val(),
        sessionToken: "<?php echo $_SESSION["sessionToken"]; ?>",
        page: currentSelectMediaPage, // added this line
        itemsPerPage: itemsPerSelectMediaPage // and this line
    };
            
    const params = new URLSearchParams();

    for (const [key,value] of Object.entries(data)) {
        params.append(key, value);
    }

    fetch('/api/v1/media/search.php?json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    }).then(response=>response.text()).then(data=>LoadSearchMediaResults(data));
}

function LoadSearchMediaResults(data)
{
    var response = JSON.parse(data);
    var results = response.data.mediaItems;
    ClearSearchMediaResults(); 
    for (let index = 0; index < results.length; index++) {
        const media = results[index];
        console.log(media);
        AddSearchMediaResult(media);
    }

    generatePaginationSelectMedia(response.data.total, itemsPerSelectMediaPage, currentSelectMediaPage);
}

function ClearSearchMediaResults()
{
    $("#selectMediaSearchResults").html("");
}
var currentSelectedMediaId = -1;
var currentSelectedMediaPath = "";

function SelectMedia(Id, path) {
    console.log(Id);
    currentSelectedMediaId = Id;
    currentSelectedMediaPath = path;
    // First, hide all icons:
    var allIcons = document.querySelectorAll('.selected-icon');
    allIcons.forEach(function(icon) {
        icon.style.display = 'none';
    });

    var item = document.getElementById(`searchMediaResult-${Id}`);
    
    // Check if the item exists
    if (item) {
        var icon = item.querySelector('.selected-icon');

        // Only show the icon if it was previously hidden
        if (icon.style.display === 'none' || icon.style.display === '') {
            icon.style.display = 'block';
        }
    } else {
        console.error(`Element with ID searchMediaResult-${Id} not found.`);
    }

    // Your existing code for SelectMedia (if any)...
}

function AcceptSelectedMedia()
{
    console.log(currentSelectedMediaId);
    pixelEditorSettings = null;
    lastPixelEditorSrc = '';
    croppedImageData = '';
    pixelatedImageData = '';

    $("#selectMediaModal").modal("hide");
    $("#"+selectMediaModalCallerId).modal("show");

    if (selectMediaImageElementOutputId != null)
    {
        var preview = document.getElementById(selectMediaImageElementOutputId);
        preview.src = currentSelectedMediaPath;
    }

    if (selectMediaImageFormInputId != null)
    {
        $("#"+selectMediaImageFormInputId).val(currentSelectedMediaId);
    }

    // Execute the callback function if provided
    if (selectMediaCallbackFunction && typeof selectMediaCallbackFunction === "function") {
        selectMediaCallbackFunction(currentSelectedMediaId, currentSelectedMediaPath);
    }
}

function AddSearchMediaResult(media) {
    var html = `<div class="inventory-item" data-bs-toggle="tooltip" id="searchMediaResult-${media.crand}" onclick="SelectMedia(${media.crand},'${media.url}')" data-bs-placement="bottom" data-bs-title="${media.name}">
                    <img src="${media.url}">
                    <i class="fas fa-check selected-icon"></i>
                </div>`;

    // Assuming you want to append the result to #selectMediaSearchResults
    $("#selectMediaSearchResults").append(html);
}

function generatePaginationSelectMedia(totalItems, itemsPerPage, currentPage) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let paginationHtml = '<div class="btn-group me-2" role="group" aria-label="Pagination group">';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            paginationHtml += `<button type="button" class="btn bg-ranked-1 active">${i}</button>`;
        } else {
            paginationHtml += `<button type="button" class="btn btn-primary" onclick="onPaginationClickSelectMedia(${i})">${i}</button>`;
        }
    }
    
    paginationHtml += '</div>';
    
    $("#pagination").html(paginationHtml);
}

function onPaginationClickSelectMedia(pageNumber) {
    // Add the selected page to the search parameters and re-run the search
    currentSelectMediaPage = pageNumber; // make sure to define this variable globally or pass it around as needed
    SearchForMedia();
}

// Expose functions for inline event handlers
window.OpenMediaUploadModal = OpenMediaUploadModal;
window.CloseMediaUploadModal = CloseMediaUploadModal;
window.UpdateMediaUploadModal = UpdateMediaUploadModal;
window.ResetMediaUploadWizardStep2 = ResetMediaUploadWizardStep2;
window.CropImageFromEditor = CropImageFromEditor;
window.SkipPixelation = SkipPixelation;
window.ApplyPixelation = ApplyPixelation;
window.MediaUploadNextStep = MediaUploadNextStep;
window.MediaUploadPrevStep = MediaUploadPrevStep;
window.GetAspectRatio = GetAspectRatio;
window.InitCropper = InitCropper;
window.OnPhotoUsageChanged = OnPhotoUsageChanged;
window.OnUploadFileChanged = OnUploadFileChanged;
window.GenerateImageFromPrompt = GeneratePromptImage;
window.UploadImageData = UploadImageData;
window.ReopenSelectMediaModal = ReopenSelectMediaModal;
window.OpenSelectMediaModal = OpenSelectMediaModal;
window.OnSelectMediaChangeSearchParams = OnSelectMediaChangeSearchParams;
window.SearchForMedia = SearchForMedia;
window.LoadSearchMediaResults = LoadSearchMediaResults;
window.ClearSearchMediaResults = ClearSearchMediaResults;
window.SelectMedia = SelectMedia;
window.AcceptSelectedMedia = AcceptSelectedMedia;
window.AddSearchMediaResult = AddSearchMediaResult;
window.generatePaginationSelectMedia = generatePaginationSelectMedia;
window.onPaginationClickSelectMedia = onPaginationClickSelectMedia;
window.PromptGenerateWithAI = PromptGenerateWithAI;

</script>

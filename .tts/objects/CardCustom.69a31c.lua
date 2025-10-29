API_URL = "https://kickback-kingdom.com/api/v1/lich/get-deck.php?lootId=9078"


function fetchDeckData()
    if(generating == false)then
        print("Fetching deck data from API...")

        WebRequest.get(API_URL, function(response)
            if response.is_error then
                print("API request failed: " .. response.error)
                return
            end
    
            local data = JSON.decode(response.text)
    

            if not data then
                print("Failed to parse JSON response!")
                return
            end

            generateDeck(data)
            generating = true
            
        end)
    end
    
end

function generateDeck(data)
    local buttons = self.getButtons()

    if not buttons or #buttons == 0 then
        print("No buttons found on this object!")
        return
    end

    local buttonPosition = buttons[1].position

    local spawnPosition = {
        self.getPosition().x + buttonPosition[1],
        self.getPosition().y + buttonPosition[2] - 0.9,
        self.getPosition().z + buttonPosition[3]
    }

    local cards = data.cards

    local cardObjects = {}
    
    local backImageURL = data.backImageUrl

    for i, card in ipairs(cards) do

        local typesString = ""

        if(cards[i].types != nil) then
            typesString = table.concat(cards[i].types, ", ").." "
        end

        if(cards[i].types == nil) then
            print("Card has no types!")
        end


        local cardData = {
            name = cards[i].name.." | "..typesString,
            description = cards[i].description,
            face = cards[i].frontImageURL,
            back = backImageURL,
            type = 0,
            quantity = cards[i].quantity
        }

        cardObjects[i] = cardData
    end

    local deckSize = getDeckSize(cardObjects)

    spawnCoroutine(cardObjects, spawnPosition, deckSize)
end

function spawnCoroutine(cardObjects, spawnPosition, deckSize)
    local index = 1
    local numberOfCardsAlreadyGenerated = 0

    local function spawnNext()
        if index > #cardObjects then 
            generating = false
            return 
        end

        spawnCardDuplicates(cardObjects[index], spawnPosition, deckSize, numberOfCardsAlreadyGenerated, function()
            index = index + 1
            spawnNext()
        end)

        numberOfCardsAlreadyGenerated = numberOfCardsAlreadyGenerated + cardObjects[index].quantity
    end

    spawnNext()
end

function spawnCardDuplicates(cardData, spawnPosition, deckSize, numberOfCardsAlreadyGenerated, onDoneCallback)
    local index = 1

    local function spawnDuplicate()
        if index > cardData.quantity then
            if onDoneCallback then onDoneCallback() end
            return
        end

        local spawnRotation = {0,-90,0}

        local cardObjectData = {
            name = cardData.name,
            description = cardData.description,
            face = cardData.face,
            back = cardData.back,
            type = cardData.type
        }

        local percentDone = (index + numberOfCardsAlreadyGenerated) / deckSize

        local pos = {
            spawnPosition[1], 
            spawnPosition[2] + (percentDone * 40), 
            spawnPosition[3]
        }

        updatePercentDoneText(percentDone)
        createCardObject(cardObjectData, pos, spawnRotation)

        index = index + 1
        Wait.time(spawnDuplicate,  0.08)
    end

    spawnDuplicate()
end

function createCardObject(cardData, spawnPosition, spawnRotation)

    local newCard = spawnObject({
        type = "CardCustom",
        position = spawnPosition,
        rotation = spawnRotation
    })

    if newCard then
        newCard.setCustomObject(cardData)
        newCard.setName(cardData.name)
        newCard.setDescription(cardData.description)
        newCard.reload()
    else
        print("Card creation failed!")
    end
end

function createButton()
    self.createButton({
        click_function = "fetchDeckData",
        function_owner = self,
        label = "",
        position = {0, 1, 0},
        rotation = {0, 0, 0},
        width = 1050,
        height = 1500,
        font_size = 300,
        color = {0, 0, 0, 0},
        font_color = {1, 1, 1, 1}
    })
end

function updatePercentDoneText(percentDone)
    local percentText = " "

    if(percentDone < 1 and percentDone > 0) then
        percentText = tostring(math.floor(percentDone * 100)).."%"
    end

    percentDoneObject.TextTool.setValue(percentText)
    percentDoneObject.TextTool.setFontColor({1 - percentDone, percentDone, 0})
end

function createPercentDoneTextObject()
    local buttons = self.getButtons()

        if not buttons or #buttons == 0 then
            print("No buttons found on this object!")
            return
        end

        local buttonPosition = buttons[1].position

        local spawnPosition = {
            self.getPosition().x + buttonPosition[1] - 3,
            self.getPosition().y + buttonPosition[2] - 0.9,
            self.getPosition().z + buttonPosition[3] 
        }

        local spawnRotation = {90,90,0}

    local object = spawnObject({
        type = "3DText",
        position = spawnPosition,
        rotation = spawnRotation,
        scale = {1, 1, 1},
        sound = false,
        snap_to_grid = false,
        callback_function = function(obj)
            percentDoneObject = obj
            obj.TextTool.setValue(" ")
            obj.TextTool.setFontSize(100)
            obj.TextTool.setFontColor({1, 1, 1})
        end
    })

    return object
end

function getDeckSize(cardObjects)
    local size = 0

    for i, object in ipairs(cardObjects) do
        size = size + object.quantity
    end

    return size
end

function onLoad()
    generating = false
    createButton()
    createPercentDoneTextObject()
end
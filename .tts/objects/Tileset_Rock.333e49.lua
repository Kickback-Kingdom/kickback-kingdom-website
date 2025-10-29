boardGUID = "068885"

function onLoad()
    local board = getObjectFromGUID(boardGUID)

    board.call("registerToBoard", { self, "sourceStone" })

end

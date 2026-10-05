// Command basic is a short example of how to use the score board.
package main

import (
	"errors"
	"fmt"
	"log"

	"worldcupscoreboard/internal/scoreboard"
)

func main() {
	board := scoreboard.NewScoreBoard(scoreboard.NewInMemoryFootballMatchRepository())

	// 1. Start two matches. Each one begins at 0 - 0.
	mexicoCanada := newMatch("Mexico", "Canada")
	spainBrazil := newMatch("Spain", "Brazil")

	check(board.StartGame(mexicoCanada))
	check(board.StartGame(spainBrazil))

	// 2. Update the scores. The numbers are the new score, not goals to add.
	check(board.UpdateScore(mexicoCanada, 0, 5))
	check(board.UpdateScore(spainBrazil, 10, 2))

	// 3. Show the board: most goals first.
	printMatches("On the board:", board.Summary())

	// 4. Finish a match. It leaves the board and moves to the finished matches.
	check(board.FinishGame(spainBrazil))

	printMatches("On the board after Spain - Brazil finished:", board.Summary())
	printMatches("Finished:", board.FinishedGames())

	// 5. Errors. Every error from the score board can be found as ScoreBoardError.
	err := board.StartGame(newMatch("Mexico", "Germany"))

	var scoreBoardError scoreboard.ScoreBoardError
	if errors.As(err, &scoreBoardError) {
		fmt.Println("Not allowed:", err)
	}
}

func newMatch(homeTeam, awayTeam string) *scoreboard.FootballMatch {
	match, err := scoreboard.NewFootballMatch(scoreboard.NewUUID(), homeTeam, awayTeam)
	check(err)

	return match
}

// check stops the example when a step that should work fails.
func check(err error) {
	if err != nil {
		log.Fatal(err)
	}
}

func printMatches(title string, matches []*scoreboard.FootballMatch) {
	fmt.Println(title)

	for position, match := range matches {
		fmt.Printf("%d. %s %d - %s %d\n", position+1, match.HomeTeam(), match.HomeScore(), match.AwayTeam(), match.AwayScore())
	}

	fmt.Println()
}

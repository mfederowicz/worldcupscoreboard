// Package scoreboard keeps a live score board of football matches.
package scoreboard

import (
	"cmp"
	"fmt"
	"slices"
	"strings"
)

// ScoreBoard starts matches, updates their scores, finishes them and lists
// the ones in progress.
type ScoreBoard struct {
	matches FootballMatchRepository
}

func NewScoreBoard(matches FootballMatchRepository) *ScoreBoard {
	return &ScoreBoard{matches: matches}
}

// StartGame starts the match and puts it on the board. A team can be in only
// one match on the board at a time.
func (b *ScoreBoard) StartGame(match *FootballMatch) error {
	for _, team := range []string{match.HomeTeam(), match.AwayTeam()} {
		if b.isPlaying(team) {
			return fmt.Errorf("%w: %q", ErrTeamAlreadyPlaying, team)
		}
	}

	if err := match.StartMatch(); err != nil {
		return err
	}

	b.matches.Save(match)

	return nil
}

// FinishGame finishes the match and removes it from the board.
func (b *ScoreBoard) FinishGame(match *FootballMatch) error {
	if err := b.checkOnBoard(match); err != nil {
		return err
	}

	if err := match.FinishMatch(); err != nil {
		return err
	}

	b.matches.Remove(match.MatchID())

	return nil
}

// UpdateScore replaces the score of a match that is on the board.
func (b *ScoreBoard) UpdateScore(match *FootballMatch, homeScore, awayScore int) error {
	if err := b.checkOnBoard(match); err != nil {
		return err
	}

	if err := match.UpdateScore(homeScore, awayScore); err != nil {
		return err
	}

	b.matches.Save(match)

	return nil
}

// Summary returns the matches in progress. Highest total score first; equal
// totals: most recently started first.
func (b *ScoreBoard) Summary() []*FootballMatch {
	return byTotalScore(b.matches.All())
}

func byTotalScore(matches []*FootballMatch) []*FootballMatch {
	slices.SortStableFunc(matches, func(x, y *FootballMatch) int {
		return cmp.Or(
			cmp.Compare(y.HomeScore()+y.AwayScore(), x.HomeScore()+x.AwayScore()),
			y.StartMatchTime().Compare(x.StartMatchTime()),
		)
	})

	return matches
}

func (b *ScoreBoard) checkOnBoard(match *FootballMatch) error {
	if _, ok := b.matches.Find(match.MatchID()); !ok {
		return fmt.Errorf("%w: %q is not on the board", ErrFootballMatchNotFound, match.MatchID())
	}

	return nil
}

func (b *ScoreBoard) isPlaying(team string) bool {
	return slices.ContainsFunc(b.matches.All(), func(live *FootballMatch) bool {
		return strings.EqualFold(team, live.HomeTeam()) || strings.EqualFold(team, live.AwayTeam())
	})
}

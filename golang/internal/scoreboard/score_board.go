// Package scoreboard keeps a live score board of football matches.
package scoreboard

import (
	"cmp"
	"fmt"
	"slices"
	"strings"
)

// ScoreBoard starts matches, updates their scores, finishes them and lists
// the ones in progress and the finished ones.
type ScoreBoard struct {
	matches         FootballMatchRepository
	finishedMatches []*FootballMatch
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

// FinishGame finishes the match, removes it from the board and keeps it in
// the list of finished games.
func (b *ScoreBoard) FinishGame(match *FootballMatch) error {
	if err := b.checkOnBoard(match); err != nil {
		return err
	}

	if err := match.FinishMatch(); err != nil {
		return err
	}

	b.matches.Remove(match.MatchID())
	b.finishedMatches = append(b.finishedMatches, match)

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

// Summary returns the matches in progress, ordered by total score.
func (b *ScoreBoard) Summary() []*FootballMatch {
	return byTotalScore(b.matches.All())
}

// FinishedGames returns the finished matches, ordered by total score.
func (b *ScoreBoard) FinishedGames() []*FootballMatch {
	return byTotalScore(slices.Clone(b.finishedMatches))
}

// byTotalScore sorts the matches: highest total score first; equal totals:
// most recently started first.
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

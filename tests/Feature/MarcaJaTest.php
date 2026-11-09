<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarcaJaTest extends TestCase
{
    use RefreshDatabase;

    private User $lucia;
    private User $alberto;
    private User $rosa;
    private User $tembe;

    protected function setUp(): void
    {
        parent::setUp();

        // Quinta-feira à noite, como no Cenário 1
        Carbon::setTestNow('2026-11-12 20:00:00');
        $this->seed(DatabaseSeeder::class);

        $this->lucia = User::where('email', 'lucia@marcaja.co.mz')->first();
        $this->alberto = User::where('email', 'alberto@marcaja.co.mz')->first();
        $this->rosa = User::where('email', 'rosa@marcaja.co.mz')->first();
        $this->tembe = User::where('email', 'jorge.tembe@marcaja.co.mz')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_paginas_privadas_pedem_login(): void
    {
        $this->get('/consultas')->assertRedirect('/login');
        $this->get('/marcar')->assertRedirect('/login');
    }

    public function test_entra_com_telefone_ou_email(): void
    {
        $this->post('/login', ['login' => '82 345 6789', 'password' => DatabaseSeeder::PASSWORD])->assertRedirect('/consultas');
        $this->assertAuthenticatedAs($this->alberto);

        $this->post('/logout');
        $this->post('/login', ['login' => 'rosa@marcaja.co.mz', 'password' => DatabaseSeeder::PASSWORD])->assertRedirect('/consultas');
        $this->assertAuthenticatedAs($this->rosa);
    }

    public function test_registo_cria_conta_de_utente(): void
    {
        $this->post('/register', [
            'name' => 'Teste Utente', 'telefone' => '+258 87 000 1111', 'email' => 't@ex.com',
            'password' => 'segredo123', 'password_confirmation' => 'segredo123',
        ])->assertRedirect('/marcar');

        $this->assertDatabaseHas('users', ['email' => 't@ex.com', 'perfil' => 'utente', 'telefone' => '870001111']);
    }

    public function test_cada_perfil_ve_so_as_suas_consultas(): void
    {
        $this->actingAs($this->lucia)->get('/consultas')
            ->assertSee('Dores de cabeça frequentes')->assertDontSee('Controlo da tensão')->assertDontSee('Inês Bila');

        $this->actingAs($this->alberto)->get('/consultas')
            ->assertSee('Controlo da tensão')->assertDontSee('Lúcia Mondlane');

        $this->actingAs($this->tembe)->get('/consultas?data=')
            ->assertSee('Alberto Nhaca')->assertDontSee('Inês Bila')->assertDontSee('Fátima Langa');

        $this->actingAs($this->rosa)->get('/consultas?data=')
            ->assertSee('Alberto Nhaca')->assertSee('Inês Bila')->assertSee('Lúcia Mondlane');
    }

    public function test_secretaria_ve_consultas_de_hoje_e_pesquisa_por_telefone(): void
    {
        $this->actingAs($this->rosa)->get('/consultas')
            ->assertSee('Fátima Langa')->assertDontSee('Inês Bila');

        $this->actingAs($this->rosa)->get('/consultas?telefone=82+345+6789')
            ->assertSee('Alberto Nhaca')->assertDontSee('Fátima Langa');
    }

    public function test_medico_nao_marca_e_utente_nao_mexe_em_consulta_alheia(): void
    {
        $this->actingAs($this->tembe)->get('/marcar')->assertForbidden();

        $doAlberto = Consulta::where('paciente_nome', 'Alberto Nhaca')->first();
        $this->actingAs($this->lucia)->patch("/consultas/{$doAlberto->id}/cancelar")->assertForbidden();
        $this->actingAs($this->lucia)->patch("/consultas/{$doAlberto->id}/concluir")->assertForbidden();
    }

    public function test_cenario_1_hora_ocupada_e_codigo_0007(): void
    {
        $sexta = '2026-11-13';
        $this->actingAs($this->lucia);

        $this->getJson("/horarios-ocupados?medico_id=3&data={$sexta}")->assertExactJson(['10:30']);

        $dados = ['paciente_nome' => 'Tomás Mondlane', 'paciente_telefone' => '84 123 4567', 'medico_id' => 3, 'data' => $sexta, 'motivo' => 'Febre'];

        $this->post('/consultas', $dados + ['hora' => '10:30'])->assertSessionHasErrors('hora');

        $this->post('/consultas', $dados + ['hora' => '09:00'])
            ->assertRedirect('/consultas')
            ->assertSessionHas('sucesso', 'Consulta marcada com sucesso! Código #0007.');

        $this->assertDatabaseHas('consultas', ['id' => 7, 'user_id' => $this->lucia->id, 'hora' => '09:00']);
    }

    public function test_nao_marca_em_dia_que_o_medico_nao_atende(): void
    {
        // O Dr. Jorge Tembe só atende de segunda a quinta
        $this->actingAs($this->alberto)->post('/consultas', [
            'paciente_nome' => 'Alberto Nhaca', 'paciente_telefone' => '823456789', 'medico_id' => 5,
            'data' => '2026-11-13', 'hora' => '09:00',
        ])->assertSessionHasErrors('data');
    }

    public function test_consulta_mensal_cria_varias_nos_dias_do_medico(): void
    {
        $this->actingAs($this->alberto)->post('/consultas', [
            'paciente_nome' => 'Alberto Nhaca', 'paciente_telefone' => '823456789', 'medico_id' => 5,
            'data' => '2026-11-18', 'hora' => '10:00', 'repetir' => 3,
        ])->assertSessionHasNoErrors();

        $serie = Consulta::where('hora', '10:00')->where('medico_id', 5)->orderBy('data')->get();
        $this->assertCount(3, $serie);
        $this->assertCount(1, $serie->pluck('serie')->unique());
        foreach ($serie as $c) {
            $this->assertContains((int) date('w', strtotime($c->data)), [1, 2, 3, 4]);
        }
    }

    public function test_cenario_2_alberto_confirma_e_cancela_e_a_vaga_fica_livre(): void
    {
        $consulta = Consulta::where('paciente_nome', 'Alberto Nhaca')->orderBy('data')->first();

        $this->actingAs($this->alberto)->patch("/consultas/{$consulta->id}/confirmar");
        $this->assertSame('confirmada', $consulta->fresh()->estadoVisivel());

        $this->actingAs($this->alberto)->patch("/consultas/{$consulta->id}/cancelar");
        $this->assertSame('cancelada', $consulta->fresh()->estadoVisivel());

        $this->getJson("/horarios-ocupados?medico_id=5&data={$consulta->data}")->assertExactJson([]);

        $this->actingAs($this->rosa)->get('/consultas?telefone=823456789')->assertSee('Cancelada');
    }

    public function test_secretaria_marca_e_a_consulta_aparece_na_conta_do_utente(): void
    {
        $this->actingAs($this->rosa)->post('/consultas', [
            'paciente_nome' => 'Alberto Nhaca', 'paciente_telefone' => '82 345 6789', 'medico_id' => 2,
            'data' => '2026-11-14', 'hora' => '08:00',
        ])->assertRedirect('/consultas?telefone=823456789');

        $this->assertDatabaseHas('consultas', ['medico_id' => 2, 'user_id' => $this->alberto->id]);
    }

    public function test_letras_grandes_ficam_guardadas_na_conta(): void
    {
        $this->actingAs($this->alberto)->post('/letras');
        $this->assertTrue($this->alberto->fresh()->letras_grandes);
        $this->actingAs($this->alberto)->get('/')->assertSee('class="letras-grandes"', false);
    }
}

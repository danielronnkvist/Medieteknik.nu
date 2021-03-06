<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forum extends MY_Controller
{

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
		$this->load->model('Forum_model');
		$this->load->helper(array('form', 'forum'));
    }

	public function index()
	{
		$this->overview();
	}

	function overview()
	{
		$this->category(0);
	}

	function category($theid = 0, $data = '')
	{
		// slug to id
		if(is_numeric($theid))
			$id = $theid;
		else
			$id = $this->Forum_model->get_id_from_slug($theid);

		// check existance of category
		if($theid !== 0 && !$this->Forum_model->category_exists($id))
			show_404();

		// load the cats and topics
		$main_data['ancestors_array']=$this->Forum_model->get_all_categories_ancestors_to($id);
		$main_data['categories_array'] = $this->Forum_model->get_all_categories_sub_to($id, 2);
		$main_data['topics_array'] = $this->Forum_model->get_topics($id);

		// pass alpng the data sent from param
		$main_data['post_data'] = $data;

		// check if posting should be enabled
		if(count($main_data['categories_array']) == 1)
		{
			$c = $main_data['categories_array'][0];
			$main_data['posting_allowed'] = $c->posting_allowed == 1;
			$main_data['is_logged_in'] = $this->login->is_logged_in();
			$main_data['guest_allowed'] = $c->guest_allowed == 1;
		}
		else
		{
			$main_data['posting_allowed'] = false;
			$main_data['is_logged_in'] = $this->login->is_logged_in();
			$main_data['guest_allowed'] =false;
		}

		// load lang data
		$main_data['lang'] = $this->lang_data;

		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('forum_overview', $main_data, true);
		$template_data['sidebar_content'] = $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	function post_topic()
	{
		// load helper for nice validity check functions
		$this->load->helper('Email_helper');

		$c = $this->Forum_model->get_all_categories_sub_to($this->input->post('cat_id'), 1);
		$c = $c[0];

		$tid = 0;
		if($c->posting_allowed == 1)
		{
			$data = array(
					'cat_id' 	=> $this->input->post('cat_id'),
					'topic'		=> $this->input->post('topic'),
					'reply'		=> $this->input->post('reply'),
					'guest'		=> false
				);

			// user logged in?
			if($this->login->is_logged_in())
			{
				$tid = $this->Forum_model->create_topic($data['cat_id'], $this->login->get_id(), $data['topic'], $data['reply']);
			}
			// guest post!
			elseif($c->guest_allowed == 1)
			{
				$data['name'] = $this->input->post('name');
				$data['email'] = $this->input->post('email');
				$data['hash'] = str_gen(15, 25);
				$data['guest'] = true;

				$tid = $this->Forum_model->create_guest_topic($data['cat_id'], $data['topic'], $data['reply'], $data['name'], $data['email'], $data['hash']);
			}

			// check if the post was successful
			if($tid)
			{
				if($data['guest'] && !$this->login->is_verified($data['email']))
				{
					// load email lib and view data
					$this->load->library('email');
					$email_data['lang'] = $this->lang_data;
					$email_data['data'] = $data;
					// email properties
					$this->email->from($this->config->item('noreply_mail'), $this->config->item('noreply_name'));
					$this->email->to($data['email']);
					$this->email->subject($this->config->item('mail_title').$email_data['lang']['email_forum_verify_title']);
					// load email from view and compile message
					$email_data['message'] = $this->load->view('emails/forum_verify', $email_data, true);
					$message = $this->load->view('templates/email', $email_data, true);
					$this->email->message($message);
					// send message
					$this->email->send();

					// do_dump($data);
					// echo $this->email->print_debugger();

					// redirect user!
					redirect('/forum/category/'.$data['cat_id'].'/verify', 'location');
				}
				else
					redirect('/forum/thread/'.$tid, 'location');
			}
			else
			{
				// if fail, load cat view with the post data along with it
				$data['message'] = 'fail';
				$this->category($this->input->post('cat_id'), $data);
			}
		}
		else
		{
			redirect('/forum/category/'.$this->input->post('cat_id'), 'location');
		}

		// do_dump($_POST);
	}

	function post_reply()
	{
		$this->load->helper('Email_helper');

		$c = $this->Forum_model->get_all_categories_sub_to($this->input->post('cat_id'), 1);
		$c = $c[0];

		if($c->posting_allowed == 1)
		{
			if($this->login->is_logged_in())
			{
				if($this->input->post('reply') != '')
				{
					// $cat_id, $user_id, $topic, $post, $date = ''
					$this->Forum_model->add_reply($this->input->post('topic_id'), $this->login->get_id(),$this->input->post('reply'));
				}

				redirect('forum/thread/'.$this->input->post('topic_id'), 'refresh');
			}
			elseif($c->guest_allowed == 1)
			{
				$data = array(
						'topic_id'	=> $this->input->post('topic_id'),
						'name' 		=> $this->input->post('name'),
						'email' 	=> $this->input->post('email'),
						'reply' 	=> $this->input->post('reply'),
						'hash'		=> str_gen(15, 25),
						'message' 	=> 'fail'
					);
				$reply = $this->Forum_model->add_guest_reply($data['topic_id'], $data['reply'], $data['name'], $data['email'], $data['hash']);

				$is_verified = '';
				if(!$this->login->is_verified($this->input->post('email')))
				{
					// load email lib and view data
					$this->load->library('email');
					$email_data['lang'] = $this->lang_data;
					$email_data['data'] = $data;
					// email properties
					$this->email->from($this->config->item('noreply_mail'), $this->config->item('noreply_name'));
					$this->email->to($data['email']);
					$this->email->subject($this->config->item('mail_title').$email_data['lang']['email_forum_verify_title']);
					// load email from view and compile message
					$email_data['message'] = $this->load->view('emails/forum_verify', $email_data, true);
					$message = $this->load->view('templates/email', $email_data, true);
					$this->email->message($message);
					// send message
					$this->email->send();

					// do_dump($data);
					// echo $this->email->print_debugger();

					// append verify to url so that the user gets a nice message
					$is_verified = '/verify';
				}

				if($reply)
					redirect('forum/thread/'.$this->input->post('topic_id').$is_verified, 'refresh');
				else
					$this->thread($data['topic_id'], $data);
			}
		}

		redirect('forum/thread/'.$this->input->post('topic_id').'/error', 'refresh');
	}

	function thread($id = 0, $post_data = '')
	{
		// check topic existance
		if(!$this->Forum_model->topic_exists($id))
			show_404();

		$main_data['replies'] = $this->Forum_model->get_replies($id);
		$main_data['topic'] = $this->Forum_model->get_topic($id);
		$main_data['ancestors_array']=$this->Forum_model->get_all_categories_ancestors_to($main_data['topic']->cat_id);
		$main_data['categories_array'] = $this->Forum_model->get_all_categories_sub_to($main_data['topic']->cat_id, 1);

		$main_data['post_data'] = $post_data;

		if(count($main_data['categories_array']) == 1)
		{
			$c = $main_data['categories_array'][0];

			if($c->posting_allowed == 1)
			{
				if($this->login->is_logged_in())
				{
					$main_data['postform'] = TRUE;
				}
				else if(!$this->login->is_logged_in() && $c->guest_allowed == 1)
				{
					$main_data['postform'] = TRUE;
					$main_data['guest'] = TRUE;
				}
			}
		}

		$main_data['lang'] = $this->lang_data;
		// composing the views
		$template_data['menu'] = $this->load->view('includes/menu',$this->lang_data, true);
		$template_data['main_content'] = $this->load->view('forum_thread', $main_data, true);
		$template_data['sidebar_content'] = $this->sidebar->get_standard();
		$this->load->view('templates/main_template',$template_data);
	}

	function report()
	{
		if($this->input->post('postid'))
		{
			$post_id = $this->input->post('postid');
			$user_id = $this->login->get_id();

			$result = array(
						'post_id' => $post_id,
						'user_id' => $user_id,
						'report'  => $this->Forum_model->report_post($post_id, $user_id)
					);

			$main_data['result'] = $result;
			$main_data['message'] = 'Reporting post id '.$post_id;
		}
		else
		{
			$main_data['result'] = false;
			$main_data['message'] = 'No post data sent';
		}
		$this->load->view('templates/json', $main_data);
	}

	function verify($email = '', $hash = '')
	{
		// do_dump($email);
		$email = urldecode($email);

		$redir = $this->Forum_model->get_topic_id_from_hash($hash, $email);

		if($redir)
		{
			$this->login->verify($hash, $email);
			redirect('forum/thread/'.$redir->topic_id.'/#replyid-'.$redir->reply_id, 'location');
		}
		else
			show_404();
	}
}
